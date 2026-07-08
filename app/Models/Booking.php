<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id', 'trip_id', 'ticket_reference', 'ticket_count', 'total_price', 'onboarding_location', 'status', 'payment_method', 'payment_status'
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'ticket_count' => 'integer',
    ];

    public function passenger()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function seats()
    {
        return $this->belongsToMany(TripSeat::class, 'booking_seat');
    }
}
