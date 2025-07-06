<?php

namespace App\Models;

use App\Traits\LogsModelActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class Merchant extends Model
{
    use LogsModelActions, EncryptsAttributes;

    protected static $logAttributes = ['status', 'amount', 'due_date'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'merchant';

    protected $fillable = [
        'assigned_to',
        'user_id',
        'business_type_id',
        'business_category_id',
        'goverment_data',
        'is_manager',
        'manager_approval',
        'cr_number',
        'pos_revenue',
        'registration_number_form',
        'vat_register',
        'vat_register_number',
        'vat_register_file',
        'return_policy',
        'return_day_count',
        'return_policy_file',
        'exchange_policy',
        'exchange_day_count',
        'exchange_policy_file',
        'cancel_policy',
        'cancel_day_count',
        'cancel_policy_file',
        'owner_name',
        'owner_iqama_number',
        'owner_iqama_image',
        'term_status',
        'status',
    ];

    protected $encryptableAttributes = [
        'cr_number',
        'pos_revenue',
        'vat_register_number',
        'return_day_count',
        'exchange_day_count',
        'cancel_day_count',
        'owner_name',
        'owner_iqama_number',
    ];

    protected $casts = [
        'payment_history' => 'array',
        'simah_api_response' => 'array',
        'external_credit_data' => 'array'
    ];
    /**
     * User relation
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assigned()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Business Type relation
     */
    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    /**
     * Accessor for business category IDs as array
     */
    public function getBusinessCategoryIdsAttribute()
    {
        return explode(',', $this->business_category_id);
    }

    public function riskManagement()
    {
        return $this->hasOne(RiskManagement::class, 'user_id', 'user_id');
    }
}
