<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    use HasFactory;

    protected $fillable = ['ip_address', 'attempts', 'last_attempt_at', 'locked_until'];

    protected $casts = [
        'last_attempt_at' => 'datetime',
    ];
}
