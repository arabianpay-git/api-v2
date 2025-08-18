<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCards extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'scheme',
        'number',
        'user_id',
        'token',
        'expiry_month',
        'expiry_year',
        'is_default'
    ];

    /**
     * Get the user that owns the card.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
