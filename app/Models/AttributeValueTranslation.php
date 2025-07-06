<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class AttributeValueTranslation extends Model
{
    use EncryptsAttributes;

    public $timestamps = false;

    protected $fillable = [
        'attribute_value_id',
        'locale',
        'value',
    ];

    protected $encryptableAttributes = ['value'];

    public function attributeValue()
    {
        return $this->belongsTo(AttributeValue::class);
    }
}
