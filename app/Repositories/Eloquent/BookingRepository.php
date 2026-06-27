<?php

namespace App\Repositories\Eloquent;

use App\Models\Booking;
use App\Models\Trip;
use App\Repositories\Contracts\BookingRepositoryInterface;

class BookingRepository implements BookingRepositoryInterface
{
    public function getTripPriceAndSeats($tripId)
    {
        /* Implement the logic to retrieve the price and available seats
        for a specific trip. For example, you can use Eloquent's findOrFail 
        method to retrieve the trip by its ID and then access its price and 
        seats relationships */
        return Trip::with('schedule')->findOrFail($tripId);
    }

    public function getPassengerBookings($userId)
    {
        /* Implement the logic to retrieve bookings 
        for a specific passenger (user) For example, 
        you can use Eloquent's where method to filter 
        bookings by user_id and eager load the related 
        trip and schedule information */
        return Booking::where('user_id', $userId)->with('trip.schedule.bus', 'seats')->get();
    }
}