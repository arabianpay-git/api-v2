<?php

namespace App\Models;

use App\Traits\LogsModelActions;
use Illuminate\Database\Eloquent\Model;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class State extends Model
{
    use LogsModelActions, EncryptsAttributes;

    protected static $logAttributes = ['status', 'amount', 'due_date'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'state';
    protected $fillable = [
        'name',
        'country_id',
    ];

    protected $encryptableAttributes = [
        'name'
    ];
    protected array $translatable = ['name'];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function cities()
    {
        return $this->hasMany(City::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function translations()
    {
        return $this->hasMany(StateTranslation::class);
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
