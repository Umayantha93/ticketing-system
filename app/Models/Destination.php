<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Destination extends Model
{
    protected $fillable = [
        'district_code',
        'name_en',
        'name_si',
        'name_ta',
        'aliases',
    ];

    protected $casts = [
        'aliases' => 'array',
    ];

    public function scopeSearchByAnyLanguage(Builder $query, string $term): Builder
    {
        $trimmed = trim($term);
        $normalized = mb_strtolower(trim($term));

        return $query->where(function (Builder $inner) use ($normalized, $trimmed) {
            $inner
                ->whereRaw('LOWER(name_en) = ?', [$normalized])
                ->orWhereRaw('LOWER(name_si) = ?', [$normalized])
                ->orWhereRaw('LOWER(name_ta) = ?', [$normalized])
                ->orWhereJsonContains('aliases', $trimmed)
                ->orWhereRaw('LOWER(name_en) LIKE ?', ["%{$normalized}%"])
                ->orWhereRaw('LOWER(name_si) LIKE ?', ["%{$normalized}%"])
                ->orWhereRaw('LOWER(name_ta) LIKE ?', ["%{$normalized}%"]);
        });
    }
}