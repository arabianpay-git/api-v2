<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class CityTranslation extends Model
{
    use EncryptsAttributes;

    public $timestamps = false;

    protected $fillable = ['locale', 'name'];
    protected $encryptableAttributes = ['name'];

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
