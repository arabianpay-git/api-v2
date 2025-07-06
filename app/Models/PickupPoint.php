<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class PickupPoint extends Model
{
    use HasFactory, EncryptsAttributes;

    protected $table = 'pickup_points';

    protected $fillable = [
        'user_id',
        'name',
        'address',
        'phone_number',
        'pick_up_status',
        'cash_on_pickup_status',
    ];

    protected $encryptableAttributes = [
        'name',
        'address',
        'phone_number',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
