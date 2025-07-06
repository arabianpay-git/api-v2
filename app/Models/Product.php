<?php

namespace App\Models;

use App\Traits\LogsModelActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class Product extends Model
{
    use LogsModelActions, EncryptsAttributes;

    protected static $logAttributes = ['status', 'amount', 'due_date'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'product';

    protected $fillable = [
        'name',
        'slug',
        'added_by',
        'user_id',
        'category_id',
        'brand_id',
        'thumbnail',
        'photos',
        'tags',
        'variants',
        'sku',
        'short_description',
        'description',
        'unit_price',
        'purchase_price',
        'discount',
        'discount_type',
        'discount_start_date',
        'discount_end_date',
        'attributes',
        'choice_options',
        'published',
        'approved',
        'featured',
        'stock_visibility_state',
        'current_stock',
        'unit',
        'weight',
        'min_qty',
        'low_stock_quantity',
        'tax',
        'tax_type',
        'shipping_type',
        'shipping_cost',
        'is_quantity_multiplied',
        'est_shipping_days',
        'number_ofsales',
        'meta_title',
        'meta_description',
        'meta_img',
        'refundable',
        'rating',
        'views',
        'reason_reject',
    ];

    protected $encryptableAttributes = [
        'name',
        'sku',
        'short_description',
        'description',
        'reason_reject',
        'unit',
        'est_shipping_days',
    ];

    protected $casts = [
        'tags' => 'array',
        'variants' => 'array',
    ];

    protected array $translatable = [
        'name',
        'short_description',
        'description',
        'tags',
        'unit',
        'meta_title',
        'meta_description'
    ];

    public function getTranslatableFields(): array
    {
        return $this->translatable ?? [];
    }

    // Relations
    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'product_attribute')
            ->withPivot('attribute_value_id')
            ->withTimestamps();
    }

    public function translations()
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function wishlists()
    {
        return $this->hasMany(ProductWishlist::class);
    }


    public function getAttributeCombinationsAttribute()
    {
        $combinations = [];

        foreach ($this->attributes as $attribute) {
            $value = $attribute->values->firstWhere('id', $attribute->pivot->attribute_value_id);
            $combinations[] = [
                'attribute' => $attribute->name,
                'value' => $value?->value,
                'attribute_id' => $attribute->id,
                'attribute_value_id' => $value?->id,
            ];
        }

        return $combinations;
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

    protected static function booted()
    {
        static::saving(function ($product) {
            if (empty($product->slug) || $product->isDirty('name.en')) {
                $product->slug = Str::slug($product->name) . '-' . uniqid();
            }
        });
    }

    public function shop()
    {
        // Assuming your shops are stored in `shop_settings` and linked via user_id
        return $this->belongsTo(\App\Models\ShopSetting::class, 'user_id', 'user_id');
    }
}
