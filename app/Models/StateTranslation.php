<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class StateTranslation extends Model
{
    use EncryptsAttributes;
    public $timestamps = false;

    protected $fillable = ['locale', 'name'];

    protected $encryptableAttributes = [
        'name'
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
