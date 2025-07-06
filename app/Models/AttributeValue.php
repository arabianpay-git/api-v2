<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class AttributeValue extends Model
{
    use EncryptsAttributes;

    protected $fillable = [
        'attribute_id',
        'value',
        'color_code',
    ];
    protected $encryptableAttributes = ['value', 'color_code'];

    protected array $translatable = ['value'];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    public function translations()
    {
        return $this->hasMany(AttributeValueTranslation::class);
    }

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (!in_array($key, $this->translatable)) {
            return $value;
        }

        $locale = app()->getLocale();

        if ($locale === 'en') {
            return $value;
        }

        $translation = $this->translations->where('locale', $locale)->first();

        return $translation?->$key ?? $value;
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_attribute')
            ->withPivot('attribute_value_id');
    }
}
