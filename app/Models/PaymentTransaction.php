<?php 
// app/Models/PaymentTransaction.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'schedule_payment_id','schedule_uuid','user_id','order_id',
        'gateway','gateway_tran_ref','token',
        'amount','currency','status','result_code','result_message','payload'
    ];

    protected $casts = [
        'payload' => 'array',
        'amount'  => 'decimal:2',
    ];
}
