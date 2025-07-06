<?php

namespace App\Models;

use App\Traits\LogsModelActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class Category extends Model
{
    use LogsModelActions, EncryptsAttributes;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'order_level',
        'banner',
        'icon',
        'featured',
        'meta_title',
        'meta_description',
    ];

    protected $encryptableAttributes = ['name'];

    protected array $translatable = [
        'name',
        'meta_title',
        'meta_description',
    ];

    protected $with = ['translations'];

    protected static $logAttributes = ['status', 'amount', 'due_date'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'category';

    protected static function booted()
    {
        static::saving(function ($category) {
            if (empty($category->slug) || $category->isDirty('name')) {
                $slug = Str::slug($category->name);
                $originalSlug = $slug;
                $counter = 1;

                while (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                    $slug = $originalSlug . '-' . $counter++;
                }

                $category->slug = $slug;
            }
        });
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function translations()
    {
        return $this->hasMany(CategoryTranslation::class);
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
