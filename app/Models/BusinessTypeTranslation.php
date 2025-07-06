<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class BusinessTypeTranslation extends Model
{
    use HasFactory, EncryptsAttributes;

    protected $fillable = [
        'locale',
        'name',
    ];

    protected $encryptableAttributes = ['name'];

    public $timestamps = false;

    public function businessType()
    {
        return $this->belongsTo(BusinessType::class);
    }
}
