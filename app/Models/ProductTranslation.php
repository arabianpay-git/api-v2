<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class ProductTranslation extends Model
{
    use EncryptsAttributes;
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'locale',
        'name',
        'unit',
        'reason_reject',
        'meta_title',
        'meta_description',
        'short_description',
        'description',
        'tags',
    ];

    protected $encryptableAttributes = [
        'name',
        'short_description',
        'description',
        'reason_reject',
        'unit',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
