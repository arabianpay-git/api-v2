<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class SupplierBank extends Model
{
    use EncryptsAttributes;

    protected $fillable = [
        'user_id',
        'bank_name',
        'account_name',
        'iban',
    ];

    protected $encryptableAttributes = [
        'bank_name',
        'account_name',
        'iban',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
