<?php

namespace App\Repositories\Eloquent;

use App\Models\Trip;
use App\Models\Schedule;
use App\Repositories\Contracts\TripRepositoryInterface;

class TripRepository implements TripRepositoryInterface
{
    public function searchTrips($origin, $destination, $date)
    {
        // Implement the logic to search for trips based on origin, destination, and date
        // For example, you can use Eloquent's query builder to filter trips

        return Trip::where('departure_date', $date)
            ->whereHas('schedule', function ($query) use ($origin, $destination) {
                $query->where('origin', $origin)->where('destination', $destination);
            })->with('schedule.bus')->get();
    }

    public function getTripWithSeats($id)
    {
        // Implement the logic to retrieve a trip along with its associated seats
        // For example, you can use Eloquent's with method to eager load the seats relationship

        return Trip::with('schedule.bus', 'seats')->findOrFail($id);
    }

    public function createSchedule(array $data)
    {
        // Implement the logic to create a new schedule record in the database
        // For example, you can use Eloquent's create method:
        return Schedule::create($data);
    }
}