<?php 
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartItemsResource extends JsonResource
{
    public function toArray($request)
    {
        $product = $this->product;

        $product_stock = $product->current_stock ?? 0;
        $priceItem = (double) ($product->unit_price ?? 0);
        $discountItem = (double) ($product->discount ?? 0);

        return [
            'product_id'      => $this->product_id,
            'product_name'    => $product->name ?? '',
            'product_image'   => $product->thumbnail ?? '', // Direct path (relative or absolute)
            'quantity'        => (int) $this->quantity,
            'options2'        => $this->variation, // JSON as string from DB
            'options'         => json_decode($this->variation, true),
            'stroked_price'   => $priceItem + $discountItem,
            'main_price'      => $priceItem,
            'total_price'     => round($priceItem * $this->quantity, 2),
            'discount'        => round($discountItem * $this->quantity, 2),
            'min_qty'         => (int) ($product->min_qty ?? 1),
            'color'           => $this->color ?? '',
            'currency_symbol' => 'SAR', // Hardcoded currency if no function
            'max_qty'         => (int) $product_stock,
            'store'           => $product->user->shop->name ?? '',
        ];
    }
}
