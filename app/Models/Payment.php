<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'seller_id',
        'order_id',
        'amount',
        'payment_details',
        'invoice_number',
        'txn_code',
        'tax_number',
        'payment_status',
    ];

    // Generate a UUID when creating a new payment
    public static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            $payment->uuid = (string) Str::uuid();
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
