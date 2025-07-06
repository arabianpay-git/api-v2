<?php

namespace App\Models;

use App\Traits\LogsModelActions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class BusinessType extends Model
{
    use HasFactory, LogsModelActions, EncryptsAttributes;

    protected $fillable = [
        'name',
        'slug',
        'risk_level',
        'order_level',
        'banner',
        'icon',
        'featured'
    ];

    protected $encryptableAttributes = ['name'];
    protected array $translatable = ['name'];

    protected static $logAttributes = ['status', 'amount', 'due_date'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'business_type';

    protected static function booted()
    {
        static::saving(function ($businessType) {
            if (empty($businessType->slug) || $businessType->isDirty('name')) {
                $slug = Str::slug($businessType->name);
                $originalSlug = $slug;
                $counter = 1;

                while (self::where('slug', $slug)->where('id', '!=', $businessType->id)->exists()) {
                    $slug = $originalSlug . '-' . $counter++;
                }

                $businessType->slug = $slug;
            }
        });
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


    public function translations()
    {
        return $this->hasMany(BusinessTypeTranslation::class);
    }

    public function businessCategories()
    {
        return $this->hasMany(BusinessCategory::class, 'business_type_id');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function merchant()
    {
        return $this->hasMany(Merchant::class);
    }
}
