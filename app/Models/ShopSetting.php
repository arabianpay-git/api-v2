<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class ShopSetting extends Model
{
    use HasFactory, EncryptsAttributes;

    protected $fillable = [
        'user_id',
        'name',
        'logo',
        'sliders',
        'banner',
        'phone_number',
        'address'
    ];

    protected $encryptableAttributes = [
        'name',
        'phone_number',
        'address'
    ];

    protected $casts = [
        'sliders' => 'array'
    ];
}
