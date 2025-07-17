<?php

namespace App\Http\Controllers;

use App\Models\AdsSlider;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopSetting;
use App\Models\Slider;
use App\Traits\ApiResponseTrait;
use Auth;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartsController extends Controller
{
    use ApiResponseTrait;
    public function setCart(Request $request)
    {
        
        $request->validate([
            'from_cart' => 'required',
            'data' => 'required|array|min:1',
            'data.*.product_id' => 'required|integer|exists:products,id',
            'data.*.quantity' => 'required|integer|min:1',
            'data.*.color' => 'nullable|string',
            'data.*.options' => 'nullable|array',
        ]);

        
        DB::beginTransaction();
        
        try {
            // Create the cart for the authenticated user:
            $cart = Cart::create([
                'user_id' => auth()->id(),
                'sub_total' => 0,            // calculated below
                'discount' => 0,             // set if you have coupons etc.
                'coupon_discount' => 0,
                'tax' => 0,
                'shipping_cost' => 0,
                'shipping_type' => '',
                'grand_total' => 0,          // calculated below
                'coupon_code' => null,
                'coupon_applied' => 0,
                'delivery_address' => '',
                'address_id' => 0,
                'carrier_id' => null,
            ]);

            $subTotal = 0;

            foreach ($request->data as $item) {
                $product = Product::findOrFail($item['product_id']);
                $unitPrice = $product->unit_price;
                $totalPrice = $unitPrice * $item['quantity'];
                $subTotal += $totalPrice;

                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'owner_id' => $product->user_id,
                    'quantity' => $item['quantity'],
                    'weight' => $product->weight ?? 0,
                    'variation' => json_encode($item['options']),
                    'code_variation' => null, // or set your variation code if needed
                    'tax' => 0, // calculate if needed
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'discount' => 0,
                    'color' => $item['color'],
                ]);
            }

            // Update cart totals:
            $cart->sub_total = $subTotal;
            $cart->grand_total = $subTotal; // + tax + shipping - discounts etc.
            $cart->save();

            DB::commit();

            $data = [
                    'cart_id' => $cart->id,
                    'grand_total' => $cart->grand_total,
            ];

            return $this->returnData($data, 'Cart created successfully');

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'errNum' => 'S500',
                'msg' => 'Failed to create cart: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getCart(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'errNum' => 'E401',
                    'msg' => 'Unauthenticated.',
                    'data' => null
                ], 401);
            }

            // Get the latest cart for the user
            $cart = Cart::with('items')->where('user_id', $user->id)->latest()->first();

            if (!$cart) {
                return response()->json([
                    'status' => false,
                    'errNum' => 'E404',
                    'msg' => 'Cart not found.',
                    'data' => null
                ], 404);
            }

            // Calculate total discount
            $total_discount = ($cart->discount ?? 0) + ($cart->coupon_discount ?? 0);

            $items = $cart->items->map(function ($item) {
                $product = Product::with('brand', 'shop')->find($item->product_id);

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_image' => asset($product->thumbnail_image), // Assuming your model has `thumbnail_image`
                    'quantity' => (int) $item->quantity,
                    'options2' => $item->options ?? "[]",
                    'options' => json_decode($item->options ?? "[]", true),
                    'stroked_price' => (float) $product->stroked_price,
                    'main_price' => (float) $product->main_price,
                    'total_price' => (float) $item->total_price,
                    'discount' => (float) $item->discount,
                    'min_qty' => (int) ($product->min_qty ?? 1),
                    'color' => $item->color ?? "",
                    'currency_symbol' => "SR",
                    'max_qty' => (int) ($product->max_qty ?? 999),
                    'store' => optional($product->shop)->name ?? "",
                ];
            });

            $data = [
                'id' => $cart->id,
                'sub_total' => (float) $cart->sub_total,
                'discount' => (float) $cart->discount,
                'coupon_discount' => (float) $cart->coupon_discount,
                'total_discount' => (float) $total_discount,
                'grand_total' => (float) $cart->grand_total,
                'shipping_cost' => (float) $cart->shipping_cost,
                'items' => $items,
            ];

            return $this->returnData($data, 'Cart fetched successfully');
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'errNum' => 'E500',
                'msg' => 'Failed to fetch cart: '.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}