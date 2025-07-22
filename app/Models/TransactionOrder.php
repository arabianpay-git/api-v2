<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionOrder extends Model
{
    use SoftDeletes;

    protected $table = 'transaction_order';
    public $incrementing = false; // لأنه uuid وليس auto-increment
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'order_id',
        'supplier_id',
        'plan_id',
        'transaction_id',
        'refund_amount',
        'total_amount',
        'admin_commission',
        'admin_Percentage',
        'tax_commission',
        'tax_percentage',
        'settlement_status',
        'supplier_amount',
        'subscription_fees',
        'supplier_conditions',
        'transfer_fees',
        'payment_fees',
        'final_amount_due_supplier',
        'settlement_date',
        'settled_amount',
        'cancel_amount',
        'purchase_invoice_number',
        'purchase_invoice',
        'order_status',
    ];

    protected $casts = [
        'supplier_conditions' => 'array',
        'settlement_date' => 'datetime',
    ];

    // 🔗 العلاقات

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function supplier()
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function plan()
    {
        return $this->belongsTo(InstalmentPlan::class, 'plan_id');
    }
}
