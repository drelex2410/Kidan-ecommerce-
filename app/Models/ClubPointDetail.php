<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClubPointDetail extends Model
{
    protected $casts = [
        'point' => 'decimal:6',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function club_point()
    {
        return $this->belongsTo(ClubPoint::class);
    }
}
