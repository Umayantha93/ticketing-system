<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'bus_id',
        'day_of_week',
        'departure_time',
        'estimated_arrival_time',
        'origin',
        'destination',
        'origin_destination_id',
        'destination_destination_id',
        'price',
    ];

    protected $casts = [
        'origin_destination_id' => 'integer',
        'destination_destination_id' => 'integer',
    ];

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function originDestination()
    {
        return $this->belongsTo(Destination::class, 'origin_destination_id');
    }

    public function destinationDestination()
    {
        return $this->belongsTo(Destination::class, 'destination_destination_id');
    }
}
