<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $table = 'cart_items';

    protected $fillable = [
        'cart_id',
        'product_id',
        'owner_id',
        'quantity',
        'weight',
        'variation',
        'code_variation',
        'tax',
        'unit_price',
        'total_price',
        'discount',
        'color',
    ];

    /**
     * The cart this item belongs to.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * The product this cart item represents.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
