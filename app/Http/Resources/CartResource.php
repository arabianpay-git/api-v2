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
            'sub_total'         => $this->sub_total,
            'discount'          => $this->discount,
            'coupon_discount'   => $this->coupon_discount,
            'total_discount'    => $this->coupon_discount + $this->discount,
            'grand_total'       => $this->grand_total,
            'shipping_cost'     => $this->shipping_cost,
            'items'             => CartItemsResource::collection($this->items),
        ];
    }
}
