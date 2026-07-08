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
        $targetDate = Carbon::parse($date)->format('Y-m-d');
        $dayOfWeek = Carbon::parse($targetDate)->format('l');

        $schedules = Schedule::with('bus')
            ->where('origin', $origin)
            ->where('destination', $destination)
            ->where('day_of_week', $dayOfWeek)
            ->get();

        $trips = new Collection();

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

            $trips->push($trip);
        }

        return Trip::with('schedule.bus', 'seats')
            ->whereIn('id', $trips->pluck('id'))
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
                'origin' => $data['origin'],
                'destination' => $data['destination'],
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

        if (TripSeat::where('trip_id', $tripId)->exists()) {
            return;
        }

        $seatsPerRow = $bus->layout_type === '2x1' ? 3 : 4;
        $rowCount = (int) ceil($bus->total_seats / $seatsPerRow);
        $seatRows = range('A', chr(ord('A') + $rowCount - 1));
        $seatNumber = 0;

        foreach ($seatRows as $row) {
            for ($number = 1; $number <= $seatsPerRow && $seatNumber < $bus->total_seats; $number++) {
                TripSeat::create([
                    'trip_id' => $tripId,
                    'seat_number' => $row . $number,
                    'status' => 'available',
                ]);
                $seatNumber++;
            }
        }
    }
}
