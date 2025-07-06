<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class BrandTranslation extends Model
{
    use EncryptsAttributes;
    public $timestamps = false;

    protected $encryptableAttributes = [
        'name',
    ];

    protected $fillable = ['locale', 'name', 'meta_title', 'meta_description'];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}
