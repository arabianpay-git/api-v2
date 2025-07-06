<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class CountryTranslation extends Model
{
    use EncryptsAttributes;
    public $timestamps = false;

    protected $fillable = ['locale', 'name'];
    protected $encryptableAttributes = ['name'];

    protected $translatable = ['name'];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Prepare the city data for API response.
     */
    public function toArray()
    {
        $data = parent::toArray();

        foreach ($this->translatable as $attribute) {
            $data[$attribute] = $this->getAttribute($attribute);
        }

        return $data;
    }
}
