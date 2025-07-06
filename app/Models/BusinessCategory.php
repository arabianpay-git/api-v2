<?php

namespace App\Models;

use App\Traits\LogsModelActions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class BusinessCategory extends Model
{
    use HasFactory, LogsModelActions, EncryptsAttributes;

    protected $encryptableAttributes = [
        'name',
    ];

    protected $fillable = [
        'business_type_id',
        'name',
        'slug',
        'order_level',
        'banner',
        'icon',
        'featured',
    ];

    protected array $translatable = ['name'];

    protected static $logAttributes = ['status', 'amount', 'due_date'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'business_category';

    public function translations()
    {
        return $this->hasMany(BusinessCategoryTranslation::class);
    }

    public function businessType()
    {
        return $this->belongsTo(BusinessType::class, 'business_type_id');
    }

    protected static function booted()
    {
        static::saving(function ($businessCategory) {
            if (empty($businessCategory->slug) || $businessCategory->isDirty('name')) {
                $slug = Str::slug($businessCategory->name);
                $originalSlug = $slug;
                $counter = 1;

                while (self::where('slug', $slug)->where('id', '!=', $businessCategory->id)->exists()) {
                    $slug = $originalSlug . '-' . $counter++;
                }

                $businessCategory->slug = $slug;
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
}
