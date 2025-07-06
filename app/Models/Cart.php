<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    protected $table = 'cart';

    protected $fillable = [
        'user_id',
        'sub_total',
        'discount',
        'coupon_discount',
        'tax',
        'shipping_cost',
        'shipping_type',
        'grand_total',
        'coupon_code',
        'coupon_applied',
        'delivery_address',
        'address_id',
        'carrier_id',
    ];

    /**
     * The user who owns the cart.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Items in this cart.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
