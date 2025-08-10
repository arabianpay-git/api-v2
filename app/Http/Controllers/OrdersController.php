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
use Illuminate\Support\Facades\Schema;
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

    public function getPendingOrders(Request $request)
    {
        $symbol = 'SR';

        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->where('payment_status', 'pending') // لو تبغى تضمّن NULL احذف هذا الشرط أو غيّره لبلوك where-orWhereNull
            ->orderByDesc('created_at')
            ->get([
                'id','reference_id','code','grand_total','coupon_discount','shipping_cost',
                'shipping_type','created_at','payment_type','payment_status'
            ]);

        $fmt = fn($v) => number_format((float)($v ?? 0), 2, '.', '');

        $data = $orders->map(function ($o) use ($symbol, $fmt) {
            // اجعل order_id = reference_id مع fallback احتياطي لو كان null
            $orderId = $o->reference_id ?: ($o->code ?: ('AP-' . (10000 + (int)$o->id)));

            return [
                'order_id'        => (string) $orderId,
                'grand_total'     => ['amount' => $fmt($o->grand_total),     'symbol' => $symbol],
                'coupon_discount' => ['amount' => $fmt($o->coupon_discount), 'symbol' => $symbol],
                'shipping_cost'   => ['amount' => $fmt($o->shipping_cost),   'symbol' => $symbol],
                'shipping_method' => (string) ($o->shipping_type ?? ''),
                'date'            => optional($o->created_at)->format('Y-m-d H:i:s'),
                'payment_type'    => $o->payment_type,
                'payment_status'  => $o->payment_status,
            ];
        })->values();

        return $this->returnData($data, 'Pending orders returned successfully');
    }


    public function getPendingOrderDetails(Request $request,$referenceId)
    {
        $userId      = $request->user()->id;
        $symbol      = 'SR';

        // اجلب كل الطلبات للمستخدم تحت نفس المرجع والحالة المعلقة
        $orders = Order::query()
            ->with(['seller', 'seller.shop']) // لو عندك علاقات
            ->where('user_id', $userId)
            ->where('reference_id', $referenceId)
            ->where('payment_status', 'pending')   // أبقِها للطلبات المعلقة فقط
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'status' => false,
                'errNum' => 'E404',
                'msg'    => 'Pending orders not found.',
            ], 404);
        }

        // دالة تنسيق المبالغ كنص بدقتين عشريتين مع فواصل آلاف مثل "4,345.00"
        $fmt = fn($v) => number_format((float)($v ?? 0), 2, '.', ',');

        // سنبني النتيجة لكل Order
        $data = $orders->map(function (Order $order) use ($symbol, $fmt, $referenceId) {

            // --- Supplier ---
            $supplier = [
                'id'      => data_get($order, 'seller.shop.id', $order->seller_id),
                'slug'    => (string) data_get($order, 'seller.shop.slug', ''),
                'user_id' => data_get($order, 'seller.shop.user_id'),
                'name'    => (string) data_get($order, 'seller.shop.name', ''),
                'logo'    => (string) data_get($order, 'seller.logo', url('/public/assets/img/placeholder.jpg')),
                'cover'   => (string) data_get($order, 'seller.banner', url('/public/assets/img/placeholder.jpg')),
                'rating'  => (float)  data_get($order, 'seller.shop.rating', 0),
            ];

            // --- Status (من general_status أو delivery_status) ---
            $slug = strtolower((string) ($order->general_status ?? $order->delivery_status ?? 'pending'));
            $statusMap = [
                'pending'   => 1,
                'confirmed' => 2,
                'processing'=> 3,
                'shipped'   => 4,
                'delivered' => 5,
                'cancelled' => 10,
                'canceled'  => 10,
            ];
            $status = [
                'id'   => $statusMap[$slug] ?? null,
                'slug' => $slug,
                'name' => ucfirst($slug),
            ];

            // --- Items: من JSON داخل product_details + جلب بيانات المنتجات
            $pd = is_array($order->product_details)
                ? $order->product_details
                : json_decode($order->product_details ?? '[]', true);

            $items = [];
            $subtotal = 0.0;

            if (is_array($pd) && count($pd)) {
                $productIds = collect($pd)->pluck('product_id')->filter()->unique()->values();
                $products = $productIds->isNotEmpty()
                    ? Product::whereIn('id', $productIds)->get(['id','name','short_description','description','thumbnail'])->keyBy('id')
                    : collect();

                foreach ($pd as $row) {
                    $pid    = (int) ($row['product_id'] ?? 0);
                    $qty    = (int) ($row['quantity'] ?? 0);
                    $uPrice = (float) ($row['unit_price'] ?? 0);
                    $tPrice = (float) ($row['total_price'] ?? ($uPrice * $qty));

                    $p = $products->get($pid);
                    $name = $p?->name ?? '-';
                    $desc = $p?->short_description ?? $p?->description ?? '';
                    $thumb= $p?->thumbnail ?? null;

                    $items[] = [
                        'id'              => $pid,
                        'name'            => (string) $name,
                        'description'     => (string) $desc,
                        'thumbnail_image' => $thumb ? (str_starts_with($thumb, 'http') ? $thumb : url($thumb)) : url('/public/assets/img/placeholder.jpg'),
                        'price' => [
                            'amount' => $fmt($uPrice),
                            'symbol' => $symbol,
                        ],
                        'quantity'        => $qty,
                    ];

                    $subtotal += $tPrice;
                }
            }

            $shippingCost   = (float) ($order->shipping_cost ?? 0);
            $couponDiscount = (float) ($order->coupon_discount ?? 0);
            $tax            = 0.0; // لا يوجد عمود ضريبة في الجدول الحالي
            $grandTotal     = (float) ($order->grand_total ?? ($subtotal + $tax + $shippingCost - $couponDiscount));

            // reference_id ثابت لكل حزمة أوامر، و"order_code" يمكنك استخدام id أو invoice_number
            $orderCode = $order->invoice_number ?: ('O-' . str_pad((string)$order->id, 6, '0', STR_PAD_LEFT));

            return [
                'reference_id' => $referenceId,
                'order_code'   => (string) $orderCode,
                'supplier'     => $supplier,
                'status'       => $status,
                'total'        => ['amount' => $fmt($grandTotal),     'symbol' => $symbol],
                'order_date'   => optional($order->created_at)->format('d-m-Y'),
                'shipping'     => [
                    'type' => (string) ($order->shipping_type ?? ''),
                    'cost' => ['amount' => $fmt($shippingCost), 'symbol' => $symbol],
                ],
                // اخترت المدينة كـ reason مثل العينة؛ غيّر المصدر لو عندك سبب إلغاء
                'reason'          => (string) ($order->shipping_city ?? ''),
                'subtotal'        => ['amount' => $fmt($subtotal),        'symbol' => $symbol],
                'coupon_discount' => ['amount' => $fmt($couponDiscount),  'symbol' => $symbol],
                'tax'             => ['amount' => $fmt($tax),             'symbol' => $symbol],
                'order_items'     => $items,
            ];
        })->values()->all();

        $this->returnData($data,"get Pending order details successfully");
    }

    public function removePendingOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::where('id', $request->order_id)
            ->where('user_id', $request->user()->id)
            ->where('payment_status', 'pending')
            ->first();

        if (!$order) {
            return response()->json([
                'status' => false,
                'errNum' => 'E404',
                'msg' => 'Pending order not found.',
            ], 404);
        }

        $order->delete();

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => 'Pending order removed successfully.'
        ]);
    }

    public function getOrders(Request $request)
    {
        $symbol = 'SR';

        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get([
                'id', 'code', 'grand_total', 'coupon_discount', 'shipping_cost',
                'shipping_type', 'created_at', 'payment_type', 'payment_status'
            ]);

        $fmt = fn($v) => number_format((float)($v ?? 0), 2, '.', '');

        $data = $orders->map(function ($o) use ($symbol, $fmt) {
            // Use 'code' if present (e.g., "AP-10005"); otherwise build a fallback
            $orderId = $o->code ?: ('AP-' . str_pad((string)$o->id, 5, '0', STR_PAD_LEFT));

            return [
                'order_id' => $orderId,
                'grand_total' => [
                    'amount' => $fmt($o->grand_total),
                    'symbol' => $symbol,
                ],
                'coupon_discount' => [
                    'amount' => $fmt($o->coupon_discount),
                    'symbol' => $symbol,
                ],
                'shipping_cost' => [
                    'amount' => $fmt($o->shipping_cost),
                    'symbol' => $symbol,
                ],
                'shipping_method' => (string) ($o->shipping_type ?? ''),
                'date' => optional($o->created_at)->format('Y-m-d H:i:s'),
                'payment_type' => $o->payment_type,
                'payment_status' => $o->payment_status,
            ];
        })->values();

        return $this->returnData($data,"Orders returned successfully");

        // If you want to return ONLY the array (without wrapper), do:
        // return response()->json($data, 200);
    }

    public function getOrderDetails(Request $request, $id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => false,
                'errNum' => 'E404',
                'msg' => 'Order not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'data' => $order
        ]);
    }
    
    public function cancelOrder(Request $request,$id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => false,
                'errNum' => 'E404',
                'msg' => 'Order not found.',
            ], 404);
        }

        if ($order->delivery_status !== 'pending') {
            return response()->json([
                'status' => false,
                'errNum' => 'E400',
                'msg' => 'Only pending orders can be cancelled.',
            ], 400);
        }

        $order->general_status = 'cancelled';
        $order->save();

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => 'Order cancelled successfully.'
        ]);
    }

    public function getAddress(Request $request, $id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => false,
                'errNum' => 'E404',
                'msg' => 'Order not found.',
            ], 404);
        }

        $address = [
            'first_name' => $order->shipping_first_name,
            'last_name' => $order->shipping_last_name,
            'address_line1' => $order->shipping_address_line1,
            'address_line2' => $order->shipping_address_line2,
            'city' => $order->shipping_city,
            'state' => $order->shipping_state,
            'country' => $order->shipping_country,
            'postal_code' => $order->shipping_postal_code,
        ];

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'data' => $address
        ]);
    }



}