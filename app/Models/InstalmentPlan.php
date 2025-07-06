<?php

namespace App\Models;

use App\Traits\LogsModelActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class InstalmentPlan extends Model
{
    use LogsModelActions, EncryptsAttributes;

    protected static $logAttributes = ['status', 'amount', 'due_date'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'instalment_plan';
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'duration',
        'finance_limit',
        'patch_days',
        'late_fee',
        'transaction_fee',
        'installments',
        'status'
    ];

    protected $encryptableAttributes = [
        'name',
        'duration',
        'finance_limit',
        'patch_days',
        'late_fee',
        'transaction_fee',
        'installments',
    ];

    protected static function booted()
    {
        static::saving(function ($instalmentPlan) {
            // Set UUID if it's not already set
            if (empty($instalmentPlan->uuid)) {
                $instalmentPlan->uuid = (string) Str::uuid();
            }

            // Generate and set the slug based on the name, ensuring it is unique
            if (empty($instalmentPlan->slug) || $instalmentPlan->isDirty('name')) {
                $slug = Str::slug($instalmentPlan->name);
                $originalSlug = $slug;
                $counter = 1;

                while (self::where('slug', $slug)->where('id', '!=', $instalmentPlan->id)->exists()) {
                    $slug = $originalSlug . '-' . $counter++;
                }

                $instalmentPlan->slug = $slug;
            }
        });
    }

    /**
     * Get the translations for the instalment plan.
     */
    public function translations()
    {
        return $this->hasMany(InstalmentPlanTranslation::class);
    }

    /**
     * Get the translated attribute.
     *
     * @param string $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (!in_array($key, ['name', 'description'])) {
            return $value;
        }

        $locale = app()->getLocale();
        $translation = $this->translations->where('locale', $locale)->first();

        return $translation ? $translation->$key : $value;
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'plan_id');
    }

    public function wallet()
    {
        return $this->hasMany(Wallet::class, 'instalment_id');
    }

    public function customer()
    {
        return $this->hasMany(Customer::class, 'instalment_id');
    }
}
