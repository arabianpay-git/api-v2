<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class InstalmentPlanTranslation extends Model
{
    use EncryptsAttributes;
    protected $fillable = ['instalment_plan_id', 'locale', 'name', 'description'];
    protected $encryptableAttributes = ['name'];
    /**
     * Get the instalment plan that owns the translation.
     */
    public function instalmentPlan()
    {
        return $this->belongsTo(InstalmentPlan::class);
    }
}
