<?php

namespace App\Models;

use App\Traits\LogsModelActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class Brand extends Model
{
    use LogsModelActions, EncryptsAttributes;

    protected $encryptableAttributes = [
        'name',
    ];

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'order_level',
        'featured',
        'meta_title',
        'meta_description',
    ];

    protected array $translatable = [
        'name',
        'meta_title',
        'meta_description',
    ];

    protected static $logAttributes = ['status', 'amount', 'due_date'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'brand';

    protected static function booted()
    {
        static::saving(function ($brand) {
            if (empty($brand->slug) || $brand->isDirty('name')) {
                $slug = Str::slug($brand->name);
                $originalSlug = $slug;
                $counter = 1;

                while (Brand::where('slug', $slug)->where('id', '!=', $brand->id)->exists()) {
                    $slug = $originalSlug . '-' . $counter++;
                }

                $brand->slug = $slug;
            }
        });
    }

    public function translations()
    {
        return $this->hasMany(BrandTranslation::class);
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
        return $this->hasMany(Product::class);
    }
}
