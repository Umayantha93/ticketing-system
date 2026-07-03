<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'bus_id', 'departure_time', 'estimated_arrival_time', 'origin', 'destination', 'price'
    ];

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }
}
