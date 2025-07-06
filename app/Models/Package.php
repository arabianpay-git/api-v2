<?php

namespace App\Models;

use App\Traits\LogsModelActions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Package extends Model
{
    use HasFactory;
    use LogsModelActions;

    protected static $logAttributes = ['status', 'amount', 'due_date'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'package';

    protected $fillable = [
        'name',
        'slug',
        'min_score',
        'max_score',
        'logo',
    ];

    protected array $translatable = ['name'];

    public function translations()
    {
        return $this->hasMany(PackageTranslation::class);
    }

    // Automatically generate slug from name
    protected static function booted()
    {
        static::saving(function ($package) {
            $baseSlug = Str::slug(is_array($package->name) ? $package->name['en'] : $package->name);
            $slug = $baseSlug;
            $counter = 1;

            while (Package::where('slug', $slug)->where('id', '!=', $package->id)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }

            $package->slug = $slug;
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

        return $translation ? $translation->$key : $value;
    }

    public function customer()
    {
        return $this->hasMany(Customer::class);
    }

    public function creditLimit()
    {
        return $this->hasMany(CustomerCreditLimit::class);
    }
}
