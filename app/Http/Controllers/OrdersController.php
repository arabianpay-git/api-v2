<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\AdsSlider;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;

use App\Models\SchedulePayment;
use App\Traits\ApiResponseTrait;
use Auth;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Str;

class OrdersController extends Controller
{
    use ApiResponseTrait;
    public function sendOrder(Request $request)
    {
        $request->validate([
            'address_id' => 'required|integer|exists:addresses,id',
        ]);

        $user = auth()->user();
        $cart = Cart::with(['items.product'])->where('user_id', $user->id)->first();

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json([
                'status' => false,
                'errNum' => 'E400',
                'msg' => 'Cart is empty or not found.',
            ], 400);
        }

        $address = Address::findOrFail($request->address_id);

        // Group cart items by seller
        $itemsGrouped = $cart->items->groupBy(function ($item) {
            return optional($item->product)->user_id; // seller ID from product owner
        });

        

        DB::beginTransaction();
        try {
            $orders = [];

            foreach ($itemsGrouped as $sellerId => $items) {
                
                // Prepare product details JSON for this seller
                $productDetails = $items->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name ?? '',
                        'quantity' => $item->quantity,
                        'price' => $item->unit_price,
                        'total' => $item->total_price,
                        'color' => $item->color,
                        'options' => json_decode($item->options ?? '[]'),
                    ];
                })->toArray();

                // Calculate grand total for this seller's products
                $grandTotal = $items->sum('total_price');

                $order = Order::create([
                    'user_id' => $user->id,
                    'seller_id' => $sellerId,
                    'product_details' => json_encode($productDetails, JSON_UNESCAPED_UNICODE),
                    'shipping_first_name' => $user->first_name,
                    'shipping_last_name' => $user->last_name,
                    'shipping_address_line1' => $address->address,
                    'shipping_city' => optional($address->city)->name ?? '',
                    'shipping_state' => optional($address->state)->name ?? '',
                    'shipping_country' => optional($address->country)->name ?? '',
                    'shipping_postal_code' => $address->postal_code,
                    'shipping_type' => $cart->shipping_type ?? 'standard',
                    'order_from' => 'mobile_api',
                    'payment_type' => 'cash_on_delivery',
                    'shipping_cost' => $cart->shipping_cost ?? 0,
                    'grand_total' => $grandTotal,
                    'coupon_discount' => 0, // or calculate if needed
                    'payment_status' => 'pending',
                    'delivery_status' => 'pending',
                    'general_status' => 'processing',
                ]);

                $grandTotal = $order->grand_total;
                $installmentCount = 3;
                $perInstallment = round($grandTotal / $installmentCount, 2);
                $startDate = now();
                $userId = $order->user_id;
                $sellerId = $order->seller_id;

                $orderInstallments = [];
                for ($i = 1; $i <= $installmentCount; $i++) {
                    $dueDate = $startDate->copy()->addDays(30 * ($i - 1));

                    SchedulePayment::create([
                    'uuid' => (string) Str::uuid(),
                    'assigned_to' => null,
                    'user_id' => $userId,
                    'seller_id' => $sellerId,
                    'order_id' => $order->id,
                    'instalment_number' => $i,
                    'due_date' => $dueDate,
                    'instalment_amount' => $perInstallment,
                    'principle_amount' => $perInstallment,
                    'late_fee' => 0,
                    'subscription_fee' => 0,
                    'shipping_amount' => 0,
                    'additional_amount' => 0,
                    'difference_amount' => 0,
                    'deducted_amount' => 0,
                    'is_late' => 0,
                    'late_days' => 0,
                    'payment_status' => $i === 1 ? 'paid' : 'pending',
                ]);

                $orderInstallments[] = [
                    'instalment_number' => $i,
                    'due_date' => $dueDate->toDateString(),
                    'instalment_amount' => $perInstallment,
                    'payment_status' => $i === 1 ? 'paid' : 'pending',
                ];
                }

                $orders[] = [
                    'order_id' => $order->id,
                    'seller_id' => $order->seller_id,
                    'grand_total' => $order->grand_total,
                    'payment_status' => $order->payment_status,
                    'delivery_status' => $order->delivery_status,
                    'installments' => $orderInstallments,
                ];
            }

            // Clear cart
            $cart->items()->delete();
            $cart->delete();
            
            DB::commit();

            return $this->returnData($orders, 'Orders placed successfully.');
           
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'errNum' => 'E500',
                'msg' => 'Order placement failed: ' . $e->getMessage(),
            ], 500);
        }
    }



}