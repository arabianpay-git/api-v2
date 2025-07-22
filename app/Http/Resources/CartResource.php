<?php 
// app/Http/Resources/CartResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'sub_total'         => (double) $this->sub_total,
            'discount'          => (double) $this->discount,
            'coupon_discount'   => (double) $this->coupon_discount,
            'total_discount'    => (double) ($this->coupon_discount + $this->discount),
            'grand_total'       => (double) $this->grand_total,
            'shipping_cost'     => (double) $this->shipping_cost,
            'items'             => CartItemsResource::collection($this->items),
        ];
    }
}
