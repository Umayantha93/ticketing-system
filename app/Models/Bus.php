<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Bus extends Model
{
    protected $fillable = [
        'user_id', 'bus_number_plate', 'phone_number', 'model', 'total_seats', 'layout_type', 'last_row_seats', 'status', 'approval_status'
    ];

    protected $casts = [
        'total_seats' => 'integer',
        'last_row_seats' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Bus $bus) {
            if ($bus->approval_status !== 'approved') {
                $bus->status = 'inactive';
            }
        });
    }

    public function scopeApprovedAndActive(Builder $query): Builder
    {
        return $query
            ->where('approval_status', 'approved')
            ->where('status', 'active');
    }

    public function busOwner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
