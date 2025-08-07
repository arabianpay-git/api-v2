<?php

namespace App\Http\Controllers;

use App\Helpers\EncryptionService;
use App\Http\Resources\CartResource;
use App\Models\Address;
use App\Models\AdsSlider;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\InstalmentPlan;
use App\Models\Order;
use App\Models\Otp;
use App\Models\Product;
use App\Models\SchedulePayment;
use App\Models\Shop;
use App\Models\ShopSetting;
use App\Models\Slider;
use App\Models\Transaction;
use App\Models\TransactionOrder;
use App\Traits\ApiResponseTrait;
use Auth;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Str;

class CartsController extends Controller
{
    use ApiResponseTrait;

    public function setCart(Request $request)
    {
        $encryptionService = new EncryptionService();
        $data = $encryptionService->decrypt($request->input('data'));

        $user = auth()->user();
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        //dd($data);
        foreach ($data as $item) {
            
            if (!is_array($item)) continue; // حماية إضافية
            $product = Product::findOrFail($item['product_id']);

            $unitPrice = $product->unit_price;
            $tax = $product->tax ?? 0;
            $discount = $product->discount ?? 0;

            if ($product->discount_type === 'percent') {
                $unitPrice -= ($unitPrice * $discount / 100);
            } elseif ($product->discount_type === 'amount') {
                $unitPrice -= $discount;
            }

            $totalPrice = $unitPrice * $item['quantity'];

            // Optional: handle color or options if needed later
            $variationData = [
                'color' => $item['color'] ?? null,
                'options' => $item['options'] ?? [],
            ];

            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->where('variation', json_encode($variationData))
                ->first();

            if ($cartItem) {
                $cartItem->quantity += $item['quantity'];
                $cartItem->total_price = $cartItem->quantity * $unitPrice;
                $cartItem->save();
            } else {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'owner_id' => $product->user_id,
                    'quantity' => $item['quantity'],
                    'variation' => json_encode($variationData),
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'tax' => $tax,
                    'discount' => $discount,
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => 'Cart updated successfully.'
        ]);
    }

    /*
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
    */

    
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
                    'product_image' => $this->fullImageUrl($product->thumbnail), // Assuming your model has `thumbnail_image`
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
    
    protected function fullImageUrl($path)
    {
        return $path ? 'https://partners.arabianpay.net'.$path : 'https://api.arabianpay.net/public/placeholder.jpg';
    }
  

    public function getCartDetails()
    {
        $user = auth()->user();
        $cart = Cart::with('items')->where('user_id', $user->id)->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'status' => true,
                'errNum' => 'S200',
                'msg' => 'Cart is empty.',
                'data' => [],
            ]);
        }

        $subTotal = $cart->items->sum('total_price');
        $tax = $cart->items->sum('tax');
        $shipping = $cart->shipping_cost ?? 0;
        $couponDiscount = $cart->coupon_discount ?? 0;

        $grandTotal = $subTotal + $tax + $shipping - $couponDiscount;

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => 'Cart details retrieved successfully.',
            'data' => [
                'sub_total' => $subTotal,
                'tax' => $tax,
                'shipping' => $shipping,
                'coupon_discount' => $couponDiscount,
                'grand_total' => $grandTotal,
            ],
        ]);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'address_id' => 'required|integer|exists:addresses,id',
            'shipping_type' => 'required|string',
            'coupon_code' => 'nullable|string'
        ]);

        $user = auth()->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'status' => false,
                'errNum' => 'E404',
                'msg' => 'Cart is empty.',
            ], 404);
        }

        // Coupon logic (optional)
        $couponDiscount = 0;
        if ($request->coupon_code) {
            $couponDiscount = 10; // For demo only. You should fetch real discount from coupons table.
            $cart->coupon_code = $request->coupon_code;
            $cart->coupon_applied = 1;
            $cart->coupon_discount = $couponDiscount;
        }

        // Set Shipping Type & Address
        $cart->address_id = $request->address_id;
        $cart->shipping_type = $request->shipping_type;
        $cart->shipping_cost = 20; // For demo. Real shipping calculation depends on method.
        $cart->save();

        // Calculate totals
        $subTotal = $cart->items->sum('total_price');
        $tax = $cart->items->sum('tax');
        $shipping = $cart->shipping_cost;
        $grandTotal = $subTotal + $tax + $shipping - $couponDiscount;

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => 'Checkout updated successfully.',
            'data' => [
                'sub_total' => $subTotal,
                'tax' => $tax,
                'shipping' => $shipping,
                'coupon_discount' => $couponDiscount,
                'grand_total' => $grandTotal,
            ]
        ]);
    }

    public function getCheckout()
    {
        $user = auth()->user();
        $cart = Cart::with('items')->where('user_id', $user->id)->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'status' => true,
                'errNum' => 'S200',
                'msg' => 'Cart is empty.',
                'data' => [],
            ]);
        }

        $address = Address::find($cart->address_id);

        $subTotal = $cart->items->sum('total_price');
        $tax = $cart->items->sum('tax');
        $shipping = $cart->shipping_cost ?? 0;
        $couponDiscount = $cart->coupon_discount ?? 0;
        $grandTotal = $subTotal + $tax + $shipping - $couponDiscount;

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => 'Checkout details retrieved successfully.',
            'data' => [
                'address' => $address ? [
                    'id' => $address->id,
                    'address_line' => $address->address
                ] : null,
                'shipping_type' => $cart->shipping_type,
                'sub_total' => $subTotal,
                'tax' => $tax,
                'shipping' => $shipping,
                'coupon_discount' => $couponDiscount,
                'grand_total' => $grandTotal,
            ]
        ]);
    }

    public function resendOtp(Request $request)
    {
        
        $request->validate([
            'phone' => 'required',
        ]);

        $phone = auth()->user()->phone_number ?? $request->phone;
        

        $otpCode = rand(1000, 9999);

        Otp::create([
            'phone' => $phone,
            'code' => $otpCode,
            'used' => 0,
            'expires_at' => now()->addMinutes(5),
        ]);

        // Send SMS - integrate your SMS service here.
        // SmsService::send($request->phone, "Your OTP is: {$otpCode}");

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => 'OTP sent successfully.'
        ]);
    }

    public function confirmOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required',
        ]);

        $phone = auth()->user()->phone_number ?? $request->phone;

        $otp = Otp::where('phone', $phone)
            ->where('code', $request->otp)
            ->where('used', 0)
            ->where('expires_at', '>=', now())
            ->latest()
            ->first();

        if (!$otp) {
            return response()->json([
                'status' => false,
                'errNum' => 'E401',
                'msg' => 'Invalid or expired OTP.'
            ], 401);
        }

        $otp->used = 1;
        $otp->save();

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => 'OTP confirmed successfully.'
        ]);
    }

    public function sendOrder(Request $request)
    {
        $request->validate([
            'address_id' => 'required',
        ]);

        //dd($request);

        $encryptionService = new EncryptionService();
        $addressID = $encryptionService->decrypt($request->input('address_id'));

       

        $user = auth()->user();
        $cart = Cart::with('items.product')->where('user_id', $user->id)->first();
        //dd($cart->items);
        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'status' => false,
                'errNum' => 'E404',
                'msg' => 'Cart is empty.',
            ], 404);
        }

        $address = Address::findOrFail($addressID);
        $itemsBySupplier = $cart->items->groupBy(function ($item) {
            return $item->product->user_id;
        });

        $referenceId = 'REF-' . now()->format('YmdHis') . '-' . $user->id . '-' . strtoupper(Str::random(3));
        $totalGrandTotal = 0;
        $allProducts = [];
        $allSuppliers = [];
        $ordersIds = [];
        

        foreach ($itemsBySupplier as $supplierId => $items) {
            $productDetails = $items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'variation' => json_decode($item->variation, true),
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                ];
            });

            $orderItems = $items->map(function ($item) {
                $description = $item->product->short_description ?? $item->product->description ?? '';

                return [
                    'id'              => optional($item->product)->id,
                    'name'            => optional($item->product)->name??"-",
                    'description'     => $description??"",
                    'thumbnail_image' => $this->fullImageUrl($item->product->thumbnail),
                    'price'           => [
                                        "amount" => $item->unit_price,
                                        "symbol" => "SR"  
                                        ],
                    'quantity'        => $item->quantity,
                ];
            });

            $subTotal = $items->sum('total_price');
            $tax = $items->sum('tax');
            $shipping = $cart->shipping_cost ?? 0;
            $couponDiscount = $cart->coupon_discount ?? 0;
            $grandTotal = $subTotal + $tax + $shipping - $couponDiscount;
            $totalGrandTotal += $grandTotal;

            $order = Order::create([
                'user_id' => $user->id,
                'seller_id' => $supplierId,
                'product_details' => $productDetails->toJson(),
                'reference_id' => $referenceId,
                'shipping_type' => $cart->shipping_type,
                'shipping_cost' => $shipping,
                'payment_type' => 'cash',
                'payment_status' => 'pending',
                'grand_total' => $grandTotal,
                'delivery_status' => 'pending',
                'general_status' => 'processing',
                'order_from' => 'web',
                'shipping_first_name' => $address->name ?? null,
                'shipping_address_line1' => $address->address ?? null,
                'shipping_city' => $address->city->name ?? null,
                'shipping_state' => $address->state->name ?? null,
                'shipping_country' => $address->country->name ?? null,
                'shipping_postal_code' => $address->postal_code ?? null,
            ]);

            $ordersIds[] = $order->id;
            $allSuppliers[] = $supplierId;
            foreach ($items as $item) {
                $allProducts[] = $item->product_id;
            }

            // Create Schedule Payments
            $instalmentPlan = InstalmentPlan::where('status', 'active')->first();
            $installmentsCount = (int) $instalmentPlan->installments;
            $intervalDays = (int) $instalmentPlan->patch_days;
            $lateFee = (float) $instalmentPlan->late_fee;
            $transactionFee = (float) $instalmentPlan->transaction_fee;
            $amountPerInstallment = round($grandTotal / $installmentsCount, 2);

            for ($i = 0; $i < $installmentsCount; $i++) {
                SchedulePayment::create([
                    'uuid' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'seller_id' => $supplierId,
                    'order_id' => $order->id,
                    'transaction_id' => ,
                    'instalment_number' => ($i + 1),
                    'due_date' => now()->addDays((int)$intervalDays * ($i + 1)),
                    'instalment_amount' => $amountPerInstallment,
                    'principle_amount' => $amountPerInstallment,
                    'late_fee' => $lateFee,
                    'subscription_fee' => $transactionFee,
                    'shipping_amount' => $shipping,
                    'payment_status' => 'pending',
                ]);
            }
        }

        // Create Transaction
        $transaction = Transaction::create([
            'uuid' => (string) Str::uuid(),
            'refrence_payment' => $referenceId,
            'user_id' => $user->id,
            'order_id' => $order->id, // لأنك تربط عدة طلبات، ليس طلب واحد فقط
            'seller_id' => $supplierId, // لأنه متعدد الموردين
            'product_ids' => json_encode(array_unique($allProducts)),
            'plan_id' => $instalmentPlan->id ?? null,
            'collected' => 0,
            'retrieved' => 0,
            'canceled' => 0,
            'loan_amount' => $totalGrandTotal,
            'loan_start_date' => now()->toDateString(),
            'loan_end_date' => now()->addMonths((int)$instalmentPlan->duration)->toDateString(),
            'loan_term' => $instalmentPlan->duration,
            'subscription_fees' => $instalmentPlan->transaction_fee,
            'credit_limit_at_time' => $user->credit_limit,
            'remaining_credit_limit' => $user->credit_limit - $totalGrandTotal,
            'payment_status' => 'pending',
            'settlement_status' => 'pending',
            'general_status' => 'active',
            'resource' => 'web',
        ]);

        TransactionOrder::create([
            'id' => (string) Str::uuid(),
            'order_id' => $order->id,
            'supplier_id' => $supplierId,
            'plan_id' => $instalmentPlan->id ?? null,
            'transaction_id' => $transaction->id,
            'refund_amount' => 0,
            'total_amount' => $order->grand_total,
            'admin_commission' => 50, // حسب نسبتك
            'admin_Percentage' => 5,   // مثال
            'tax_commission' => 10,    // مثال
            'tax_percentage' => 15,    // مثال
            'settlement_status' => 0,
            'supplier_amount' => $order->grand_total - 50 - 10,
            'subscription_fees' => $instalmentPlan->transaction_fee,
            'supplier_conditions' => json_encode([]),
            'transfer_fees' => 0,
            'payment_fees' => 0,
            'final_amount_due_supplier' => $order->grand_total - 50 - 10,
            'settled_amount' => 0,
            'cancel_amount' => 0,
            'order_status' => 'waiting',
        ]);


        $data = [[
            'reference_id'      => $referenceId,
            'order_code'        => (string)$order->id,
            'supplier'          => [
                                    "id" => $order->seller->shop->id,
                                    "slug" => $order->seller->shop->slug,
                                    "user_id" => $order->seller->shop->user_id,
                                    "name" => $order->seller->shop->name??"-",
                                    'logo' => $order->seller->logo?'https://partners.arabianpay.net'.$order->seller->logo:'https://api.arabianpay.net/public/placeholder.jpg',
                                    "cover" => $order->seller->banner?$order->seller->banner:'https://api.arabianpay.net/public/placeholder.jpg',
                                    "rating" => $order->seller->shop->rating??0,
                                   ],
            'status'            => [
                                     "id" => 1,
                                     "slug" => $order->general_status,
                                     "name" => $order->general_status   
                                    ],
            'total'             => [
                                        "amount" => $order->grand_total,
                                        "symbol" => "SR"
                                    ],
            'order_date'        => date('d-m-Y', strtotime($order->created_at)),
            'shipping'          => [
                'type'          => $order->shipping_type,
                'cost'          => [
                                     "amount" => $order->shipping_cost,
                                     "symbol" => "SR"
                                    ],
            ],
            'reason'            => "",
            'subtotal'          => [
                                      "amount" => $subTotal,
                                      "symbol" => "SR"  
                                    ],
            'coupon_discount'   => [
                                      "amount" => $couponDiscount,
                                      "symbol" => "SR"  
                                    ],
            'tax'               => [
                                      "amount" => $tax,
                                      "symbol" => "SR"  
                                    ],
            'order_items'       => $orderItems
        ]];

        $cart->items()->delete();
        $cart->delete();

        return $this->returnData($data,"Order placed successfully.");

    }


    public function createSanadNafith(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id'
        ]);

        $user = auth()->user();
        $order = Order::where('id', $request->order_id)
                    ->where('user_id', $user->id)
                    ->first();

        if (!$order) {
            return response()->json([
                'status' => false,
                'errNum' => 'E404',
                'msg' => 'Order not found.',
            ], 404);
        }

        // Example: Generate a Sanad Nafith UUID (you can later store it in your DB)
        $sanadNumber = 'SNF-' . now()->format('Ymd') . '-' . $order->id . '-' . Str::random(4);

        // Here you can push this to Nafith API if required
        // Or just save it internally in your database.

        // Return سند
        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => 'Sanad Nafith created successfully.',
            'data' => [
                'order_id' => $order->id,
                'sanad_number' => $sanadNumber,
                'amount' => $order->grand_total,
                'currency' => 'SAR',
                'user' => $user->first_name . ' ' . $user->last_name,
                'due_date' => now()->addDays(3)->format('Y-m-d'),
            ]
        ]);
    }







}