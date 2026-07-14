<?php

namespace App\Repositories\Eloquent;

use App\Models\Bus;
use App\Models\Trip;
use App\Models\Schedule;
use App\Models\TripSeat;
use App\Repositories\Contracts\TripRepositoryInterface;
use Carbon\Carbon;

class TripRepository implements TripRepositoryInterface
{
    public function searchTrips($origin, $destination, $date)
    {
        $origin = $this->normalizeLocationName($origin);
        $destination = $this->normalizeLocationName($destination);

        $targetDate = Carbon::parse($date)->format('Y-m-d');
        $dayOfWeek = Carbon::parse($targetDate)->format('l');

        $schedules = Schedule::with('bus')
            ->where('origin', $origin)
            ->where('destination', $destination)
            ->where('day_of_week', $dayOfWeek)
            ->whereHas('bus', fn ($query) => $query->approvedAndActive())
            ->get();

        $tripIds = [];

        foreach ($schedules as $schedule) {
            $trip = Trip::firstOrCreate(
                [
                    'schedule_id' => $schedule->id,
                    'departure_date' => $targetDate,
                ],
                [
                    'status' => 'scheduled',
                ]
            );

            $this->ensureTripSeats($trip->id, $schedule->bus);

            $tripIds[] = $trip->id;
        }

        return Trip::with('schedule.bus', 'seats')
            ->whereIn('id', $tripIds)
            ->orderBy('id')
            ->get();
    }

    public function getTripWithSeats($id)
    {
        $trip = Trip::with('schedule.bus', 'seats')->findOrFail($id);

        $this->ensureTripSeats($trip->id, $trip->schedule?->bus);

        return $trip->fresh(['schedule.bus', 'seats']);
    }

    public function createSchedule(array $data)
    {
        return Schedule::updateOrCreate(
            [
                'bus_id' => $data['bus_id'],
                'origin' => $this->normalizeLocationName($data['origin']),
                'destination' => $this->normalizeLocationName($data['destination']),
                'day_of_week' => $data['day_of_week'],
                'departure_time' => $data['departure_time'],
            ],
            [
                'estimated_arrival_time' => $data['estimated_arrival_time'],
                'price' => $data['price'],
            ]
        );
    }

    private function ensureTripSeats(int $tripId, ?Bus $bus): void
    {
        if (!$bus) {
            return;
        }

        $existingSeats = TripSeat::query()
            ->where('trip_id', $tripId)
            ->get()
            ->keyBy('seat_number');

        $requiredSeatNumbers = $this->buildSeatNumbers($bus);

        foreach ($requiredSeatNumbers as $seatNumber) {
            if ($existingSeats->has($seatNumber)) {
                continue;
            }

            TripSeat::create([
                'trip_id' => $tripId,
                'seat_number' => $seatNumber,
                'status' => 'available',
            ]);
        }

        $staleSeatNumbers = $existingSeats->keys()->diff($requiredSeatNumbers);

        foreach ($staleSeatNumbers as $seatNumber) {
            $seat = $existingSeats->get($seatNumber);

            if ($seat && $seat->status === 'available' && !$seat->bookings()->exists()) {
                $seat->delete();
            }
        }
    }

    private function buildSeatNumbers(Bus $bus): array
    {
        $seatNumbers = [];

        foreach ($this->buildRowCounts((int) $bus->total_seats, $this->getStandardRowCapacity($bus), (int) $bus->last_row_seats) as $rowIndex => $seatCount) {
            $rowLabel = $this->getRowLabel($rowIndex);

            for ($number = 1; $number <= $seatCount; $number++) {
                $seatNumbers[] = $rowLabel . $number;
            }
        }

        return $seatNumbers;
    }

    private function buildRowCounts(int $totalSeats, int $standardRowCapacity, int $lastRowSeats): array
    {
        if ($totalSeats <= 0) {
            return [];
        }

        $rearRowSeats = min(max(1, $lastRowSeats), $totalSeats);
        $frontSectionSeats = $totalSeats - $rearRowSeats;

        if ($frontSectionSeats === 0) {
            return [$rearRowSeats];
        }

        $frontRowCount = max(1, (int) ceil($frontSectionSeats / $standardRowCapacity));
        $baseSeatsPerRow = intdiv($frontSectionSeats, $frontRowCount);
        $extraSeats = $frontSectionSeats % $frontRowCount;
        $rowCounts = [];

        for ($rowIndex = 0; $rowIndex < $frontRowCount; $rowIndex++) {
            $rowCounts[] = $baseSeatsPerRow + ($rowIndex < $extraSeats ? 1 : 0);
        }

        $rowCounts[] = $rearRowSeats;

        return array_values(array_filter($rowCounts));
    }

    private function getStandardRowCapacity(Bus $bus): int
    {
        return $bus->layout_type === '2x1' ? 3 : 4;
    }

    private function getRowLabel(int $rowIndex): string
    {
        $label = '';
        $position = $rowIndex;

        do {
            $label = chr(65 + ($position % 26)) . $label;
            $position = intdiv($position, 26) - 1;
        } while ($position >= 0);

        return $label;
    }

    private function normalizeLocationName(string $location): string
    {
        return match ($location) {
            'Colombo' => 'Pettah Bus Stand',
            'Kurunegala' => 'Kurunagala',
            'Nuwara Eliya' => 'Nuwaraeliya',
            default => $location,
        };
    }
}
