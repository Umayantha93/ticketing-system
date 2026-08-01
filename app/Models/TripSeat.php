<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripSeat extends Model
{
    protected $fillable = [
        'trip_id', 'seat_number', 'status', 'passenger_gender',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_seat');
    }
}