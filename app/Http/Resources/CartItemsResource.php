<?php

// app/Http/Resources/CartItemsResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartItemsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'product_id'    => $this->product_id,
            'product_name'  => $this->product->name ?? null,
            'thumbnail'     => $this->product->thumbnail ?? null,
            'quantity'      => $this->quantity,
            'unit_price'    => (double) $this->unit_price,
            'total_price'   => (double) $this->total_price,
            'variation'     => json_decode($this->variation, true),
        ];
    }
}
