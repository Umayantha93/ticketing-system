<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $fillable = [
        'schedule_id', 'departure_date', 'status'
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function seats()
    {
        return $this->hasMany(TripSeat::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}