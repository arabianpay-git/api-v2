<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class AttributeTranslation extends Model
{
    use EncryptsAttributes;
    public $timestamps = false;

    protected $fillable = [
        'attribute_id',
        'locale',
        'name',
    ];

    protected $encryptableAttributes = ['name'];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
