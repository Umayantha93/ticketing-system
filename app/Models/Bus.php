<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bus extends Model
{
    protected $fillable = [
        'user_id', 'bus_number_plate', 'model', 'total_seats', 'layout_type', 'status', 'approval_status'
    ];

    public function busOwner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
