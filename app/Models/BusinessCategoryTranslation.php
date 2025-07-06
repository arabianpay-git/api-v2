<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class BusinessCategoryTranslation extends Model
{
    use HasFactory, EncryptsAttributes;

    protected $encryptableAttributes = [
        'name',
    ];

    protected $fillable = [
        'business_category_id',
        'locale',
        'name',
    ];

    public function businessCategory()
    {
        return $this->belongsTo(BusinessCategory::class);
    }
}
