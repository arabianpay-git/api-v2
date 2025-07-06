<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $table = 'addresses';

    protected $fillable = [
        'user_id',
        'address',
        'name',
        'country_id',
        'state_id',
        'city_id',
        'longitude',
        'latitude',
        'postal_code',
        'phone',
        'set_default',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'set_default' => 'boolean',
    ];

    /**
     * User who owns the address.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Country of the address.
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * State/Region of the address.
     */
    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    /**
     * City of the address.
     */
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }
}
