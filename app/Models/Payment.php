<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'booking_id',
        'user_id',
        'trip_id',
        'payment_reference',
        'method',
        'status',
        'gross_amount',
        'base_fare_amount',
        'service_charge_amount',
        'other_charge_amount',
        'owner_payout_amount',
        'admin_service_amount',
        'admin_other_amount',
        'admin_profit_amount',
        'paid_at',
        'meta',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'base_fare_amount' => 'decimal:2',
        'service_charge_amount' => 'decimal:2',
        'other_charge_amount' => 'decimal:2',
        'owner_payout_amount' => 'decimal:2',
        'admin_service_amount' => 'decimal:2',
        'admin_other_amount' => 'decimal:2',
        'admin_profit_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'meta' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
