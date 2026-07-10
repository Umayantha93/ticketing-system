<?php

namespace App\Repositories\Eloquent;

use App\Models\Bus;
use App\Models\Trip;
use App\Models\Schedule;
use App\Models\TripSeat;
use App\Repositories\Contracts\TripRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
        // Implement the logic to retrieve a trip along with its associated seats
        // For example, you can use Eloquent's with method to eager load the seats relationship

        return Trip::with('schedule.bus', 'seats')->findOrFail($id);
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

        if (TripSeat::query()->get()->contains(fn (TripSeat $seat) => $seat->trip_id === $tripId)) {
            return;
        }

        $seatRows = ['A', 'B', 'C'];
        $seatLimit = min($bus->total_seats, count($seatRows) * 4);

        $seatNumber = 0;
        foreach ($seatRows as $row) {
            for ($number = 1; $number <= 4 && $seatNumber < $seatLimit; $number++) {
                TripSeat::create([
                    'trip_id' => $tripId,
                    'seat_number' => $row . $number,
                    'status' => 'available',
                ]);
                $seatNumber++;
            }
        }
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
