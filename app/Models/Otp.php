<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Otp extends Model
{
    protected $fillable = ['phone', 'code', 'expires_at', 'sends','used','id_number'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used'       => 'boolean',
    ];

    // scope: only active (not expired, not used)
    public function scopeActive($query)
    {
        return $query->where('used', false)
            ->where('expires_at', '>', Carbon::now());
    }
}
