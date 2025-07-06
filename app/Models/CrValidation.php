<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class CrValidation extends Model
{
    use EncryptsAttributes;

    protected $table = "cr_validations";

    protected $fillable = [
        'cr_data',
        'email'
    ];

    protected $encryptableAttributes = ['email'];
}
