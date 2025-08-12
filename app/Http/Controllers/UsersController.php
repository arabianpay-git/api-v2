<?php

namespace App\Http\Controllers;

use App\Models\AdsSlider;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CustomerCreditLimit;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Product;
use App\Models\SchedulePayment;
use App\Models\Shop;
use App\Models\ShopSetting;
use App\Models\Slider;
use App\Models\Transaction;
use App\Traits\ApiResponseTrait;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    use ApiResponseTrait;

    public function getProfile(Request $request)
    {
        $userId = $request->user()->id;

        $totalOrder = DB::table('orders')->where('user_id', $userId)->count();

        $schedulePayments = SchedulePayment::where('due_date', '<=', Carbon::now())
            ->where('payment_status', '!=', 'paid')
            ->where('user_id', $userId)
            ->get();

        $totalDue = $schedulePayments->sum('instalment_amount');
        $totalPaid = SchedulePayment::where('user_id', $userId)->where('payment_status', 'paid')->sum('instalment_amount');
        $limit = CustomerCreditLimit::where('user_id', $userId)->sum('limit_arabianpay_after');

        // Payments due soon formatted
        $paymentDueSoon = Transaction::where('user_id', $userId)
            ->with(['schedulePayments' => function ($q) {
                $q->orderBy('due_date');
            }, 'store'])
            ->get()
            ->map(function ($tx) {
                return [
                    "transaction_id" => $tx->uuid,
                    "reference_id" => $tx->reference_id??'--',
                    "name_shop" => $tx->store->name ?? '--',
                    "schedule_payments" => $tx->schedulePayments->map(function ($sp) {
                        return [
                            "payment_id" => $sp->uuid,
                            "reference_id" => $sp->reference_id,
                            "name_shop" => "",
                            "installment_number" => $sp->installment_number,
                            "current_installment" => $sp->is_current_installment,
                            "date" => Carbon::parse($sp->due_date)->format('M d, Y'),
                            "amount" => [
                                "amount" => number_format($sp->instalment_amount, 2),
                                "symbol" => "SR"
                            ],
                            "late_fee" => [
                                "amount" => number_format($sp->late_fee, 2),
                                "symbol" => "SR"
                            ],
                            "status" => [
                                "name" => ucfirst($sp->payment_status),
                                "slug" => $sp->payment_status
                            ]
                        ];
                    })
                ];
            });

        // Load sliders, banners, top store, etc.
        $dashboardSlider = AdsSlider::take(10)->get()->map(function ($item) {
            return [
                'image' => $item->image?'https://core.arabianpay.com'.$item->image:'https://api.arabianpay.com/uploads/sliders/default_cover.png',
                'image_id' => "185507",
                'target' => [
                    'type' => 'brand',
                    'id' => 1,
                    'name' => 'Generic',
                    'image' => $item->image?'https://core.arabianpay.com'.$item->image:'https://api.arabianpay.com/uploads/sliders/default_cover.png',
                    'rating' => 0
                ]
            ];
        });

        $topDealSlider = $dashboardSlider; // أو اجلبها من جدول آخر إن أردت

        $adBannerOne = $dashboardSlider->take(1); // أو خصصها من جدول آخر أو شرط معين
        $topStore = ShopSetting::
        where('name','!=',null)
        ->where('name','!=',"")
        ->limit(20)
        ->get()
        ->map(function ($shop) {
            return [
                "id" => $shop->id,
                "slug" => $shop->slug,
                "user_id" => $shop->user_id,
                "name" => $shop->name,
                'logo' => $shop->logo?'https://partners.arabianpay.net'.$shop->logo:'https://api.arabianpay.net/public/placeholder.jpg',
                "cover" => $shop->banner?$shop->banner:'https://api.arabianpay.net/public/placeholder.jpg',
                "rating" => $shop->rating,
            ];
        });

        $data = [
            'total_order' => $totalOrder,
            'total_due' => ['amount' => number_format($totalDue, 2), 'symbol' => 'SR'],
            'total_paid' => ['amount' => number_format($totalPaid, 2), 'symbol' => 'SR'],
            'limit' => ['amount' => number_format($limit, 2), 'symbol' => 'SR'],
            'payment_due_soon' => $paymentDueSoon,
            'dashboard_slider' => $dashboardSlider,
            'top_deal_slider' => $topDealSlider,
            'ad_banner_one' => $adBannerOne,
            'top_store' => $topStore,
        ];

        return $this->returnData($data);
    }


    public function getInfo(Request $request)
    {
        $user = Auth::user();

        $data = [
            'id' => $user->id,
            'name' => $user->first_name . ' ' . $user->last_name,
            'business_name' => $user->business_name??'-',
            'email' => $user->email,
            'id_number' => $user->iqama??0,    // assuming iqama is your id_number
            'phone' => $user->phone_number,
            'token' => $request->bearerToken(), 
            'complete' => 1, // or check from user profile completeness if needed
            "package"=> [
                    "slug"=> "gold",
                    "name"=> "Gold Package",
                    "logo"=> "http://api.arabianpay.co/public/assets/img/packages/02.png"
                ] // or fetch user's subscription if applicable
        ];

        return $this->returnData($data);
    }

    public function getPayments(Request $request)
    {
        $userId = $request->user()->id;

        // اجلب مدفوعات المستخدم
        $rows = SchedulePayment::query()
            ->where('user_id', $userId)
            ->whereNotNull('transaction_id')  // لضمان التجميع
            ->orderBy('transaction_id')
            ->orderBy('instalment_number')
            ->get([
                'uuid',
                'transaction_id',   // هذا هو المرجع النصّي لديك
                'order_id',
                'seller_id',
                'instalment_number',
                'due_date',
                'instalment_amount',
                'late_fee',
                'payment_status',
            ]);

        if ($rows->isEmpty()) {
            return $this->returnData([], 'No payments found');
        }

        // جهّز أسماء المتاجر لكل reference_id (transaction_id في schedule_payments)
        $refIds = $rows->pluck('transaction_id')->filter()->unique()->values();
        $shopsByRef = Order::query()
            ->with(['seller', 'seller.shop']) // إن توفرت العلاقات
            ->whereIn('reference_id', $refIds)
            ->get(['id','reference_id','seller_id'])
            ->groupBy('reference_id')
            ->map(fn ($g) => (string) data_get($g->first(), 'seller.shop.name', ''));

        $fmt = fn($v) => number_format((float)($v ?? 0), 2, '.', '');

        $payments = $rows->groupBy('transaction_id')
            ->map(function ($group, $ref) use ($shopsByRef, $fmt) {
                $shopName = (string) ($shopsByRef[$ref] ?? '');

                $schedule = $group->map(function ($p) use ($fmt) {
                    $due = $p->due_date ? Carbon::parse($p->due_date) : null;

                    return [
                        'payment_id'         => (string) $p->uuid,
                        'reference_id'       => (string) $p->transaction_id, // نفس المرجع
                        'name_shop'          => '', // يمكن تعبئته إن رغبت على مستوى القسط
                        'installment_number' => (int) $p->instalment_number,
                        // اعتبر القسط الحالي = نفس الشهر ولم يُدفع
                        'current_installment'=> $due ? ($p->payment_status !== 'paid' && $due->isCurrentMonth()) : false,
                        'date'               => $due ? $due->translatedFormat('M d, Y') : null,
                        'amount'             => [
                            'amount' => $fmt($p->instalment_amount),
                            'symbol' => 'SR',
                        ],
                        'late_fee'           => [
                            'amount' => $fmt($p->late_fee),
                            'symbol' => 'SR',
                        ],
                        'status'             => [
                            'name' => $this->mapPaymentStatus($p->payment_status),
                            'slug' => (string) $p->payment_status,
                        ],
                    ];
                })->values();

                return [
                    'transaction_id'    => (string) $ref,     // تجميعة المجموعة
                    'reference_id'      => (string) $ref,     // نفس الحقل للحفاظ على الشكل
                    'name_shop'         => $shopName,         // اسم المتجر على مستوى المجموعة
                    'schedule_payments' => $schedule,
                ];
            })
            ->values();

        return $this->returnData($payments, 'Payments fetched successfully');
    }

    /**
     * خرائط أسماء الحالات لعرض ودّي
     */
    private function mapPaymentStatus(?string $slug): string
    {
        $s = strtolower((string) $slug);
        return match ($s) {
            'paid'      => 'Paid',
            'pending'   => 'Pending',
            'failed'    => 'Failed',
            'on_hold',
            'hold'      => 'On Hold',
            'canceled',
            'cancelled' => 'Canceled',
            default     => ucfirst($s ?: 'Unknown'),
        };
    }
    public function getSpent(Request $request)
    {

        $userId = $request->user()->id;
        $data = [
            'status' => true,
            'errNum' => 'S200',
            'msg' => 'Spending stats fetched successfully.',
            'data' => [
                'credit_limit' => [
                    'amount' => 5000,
                    'currency' => 'SR',
                ],
                'total_spent' => [
                    'amount' => $this->getTotalSpentPrincipal($userId),
                    'currency' => 'SR',
                ],
                'total_cate_purchases' => [
                    $this->getCategoryPurchasesFromJson($userId)
                ],
                'monthly_spending_stats' => [
                    [
                        'date' => '2025-06',
                        'spent' => [
                            'amount' => 1000,
                            'currency' => 'SR',
                        ]
                    ],
                    [
                        'date' => '2025-07',
                        'spent' => [
                            'amount' => 500,
                            'currency' => 'SR',
                        ]
                    ]
                ],
                'top_store' => [
                    [
                        'store_id' => 5,
                        'store_name' => 'Extra',
                        'amount_spent' => 600,
                        'currency' => 'SR',
                    ],
                    [
                        'store_id' => 9,
                        'store_name' => 'Jarir',
                        'amount_spent' => 400,
                        'currency' => 'SR',
                    ]
                ]
            ]
        ];

        return $this->returnData($data);

    }

    function getCategoryPurchasesFromJson(int $userId, string $currency = 'SR'): array
    {
        $orders = Order::query()
            ->where('user_id', $userId)
            ->where('general_status', 'done')   // عدّلها حسب حالتك
            ->where('payment_status', 'paid')   // عدّلها حسب حالتك
            ->get(['product_details']);

        $totals = []; // [category_id => amount]

        foreach ($orders as $order) {
            $items = is_array($order->product_details)
                ? $order->product_details
                : json_decode($order->product_details, true);

            if (!is_array($items)) continue;

            foreach ($items as $it) {
                $catId    = (int) ($it['category_id'] ?? 0);
                $price    = (float) ($it['price'] ?? 0);
                $qty      = (float) ($it['quantity'] ?? 0);
                if ($catId <= 0 || $price <= 0 || $qty <= 0) continue;

                $totals[$catId] = ($totals[$catId] ?? 0) + ($price * $qty);
            }
        }

        if (empty($totals)) return [];

        // جلب أسماء الفئات دفعة واحدة
        $names = Category::whereIn('id', array_keys($totals))
            ->pluck('name', 'id'); // أو name_ar لو تبغى العربي

        // تجهيز الناتج بالشكل المطلوب
        $out = [];
        foreach ($totals as $catId => $amount) {
            if ($amount <= 0) continue;
            $out[] = [
                'category_id'   => $catId,
                'category_name' => (string) ($names[$catId] ?? 'Unknown'),
                'amount'        => (float) $amount,
                'currency'      => $currency,
            ];
        }

        // ترتيب تنازليًا حسب المبلغ
        usort($out, fn($a,$b) => $b['amount'] <=> $a['amount']);

        return $out;
    }

    public function getTotalSpentPrincipal(int $userId): float
    {
        return (float) SchedulePayment::query()
            ->where('user_id', $userId)
            ->where('payment_status', 'paid')   // أو القيمة المطابقة في نظامك
            ->sum('instalment_amount');
    }
    public function getCards(Request $request)
    {
        // Assuming you have a method to fetch user's cards
        $userId = $request->user()->id;
        $cards = []; // Fetch user's cards from the database

        $data =[
                                [
                                    "id"=> 6,
                                    "type"=> "Credit",
                                    "scheme"=> "Visa",
                                    "number"=> "4000 00## #### 0002",
                                    "token"=> "2C4654BC67A3E935C6B691FD6C8374BE",
                                    "is_default"=> false
                                ],
                                [
                                    "id"=> 7,
                                    "type"=> "Debit",
                                    "scheme"=> "Visa",
                                    "number"=> "4575 53## #### 0459",
                                    "token"=> "394154BC67A3EF34C7B093FD618778B8",
                                    "is_default"=> false
                                ]
                            ];

        return $this->returnData($data);

    }


    public function getPaymentDetails(Request $request, string $uuid)
    {
        $symbol = 'SR';
        $fmt    = fn($v) => number_format((float)($v ?? 0), 2, '.', ''); // "226.00" بدون فواصل آلاف

        // 1) المعاملة (Transaction) حسب UUID
        $tx = Transaction::where('uuid', $uuid)->first([
            'id','uuid','refrence_payment','user_id','order_id',
            'collected','retrieved','canceled','subscription_fees'
        ]);
        if (!$tx || $tx->user_id != $request->user()->id) {
            return response()->json(['status'=>false,'errNum'=>'E404','msg'=>'Transaction not found.'], 404);
        }

        // 2) الأقساط المرتبطة بهذه المعاملة (transaction_id = uuid)
        $payments = SchedulePayment::where('user_id', $tx->user_id)
            ->where('transaction_id', $uuid)
            ->orderBy('instalment_number')
            ->get([
                'id','uuid','order_id','instalment_number','due_date',
                'instalment_amount','late_fee','subscription_fee','payment_status'
            ]);

        if ($payments->isEmpty()) {
            return response()->json(['status'=>false,'errNum'=>'E404','msg'=>'Payments not found.'], 404);
        }

        // 3) الطلب المرتبط (لو transaction.order_id فارغ خذ أول order_id من الأقساط)
        $orderId = $tx->order_id ?: (int) $payments->first()->order_id;
        $order   = Order::where('id', $orderId)->first([
            'id','code','reference_id','invoice_number','product_details',
            'shipping_type','shipping_cost','coupon_discount','grand_total',
            'delivery_status','payment_type','created_at','seller_id'
        ]);
        if (!$order) {
            return response()->json(['status'=>false,'errNum'=>'E404','msg'=>'Order not found.'], 404);
        }

        // اسم المتجر (لو عندك علاقات shop على البائع، فعّل with في الاستعلام السابق واستخدمها)
        $shopName = (string) data_get($order, 'seller.shop.name', '');

        // 4) عناصر الطلب من JSON + ترطيب معلومات المنتجات
        $pd = is_array($order->product_details)
            ? $order->product_details
            : json_decode($order->product_details ?? '[]', true);

        $orderItems = [];
        $subTotal   = 0.0;

        if (is_array($pd) && count($pd)) {
            $productIds = collect($pd)->pluck('product_id')->filter()->unique();
            $products   = $productIds->isNotEmpty()
                ? Product::whereIn('id', $productIds)->get(['id','name','short_description','description','thumbnail'])->keyBy('id')
                : collect();

            foreach ($pd as $row) {
                $pid    = (int) ($row['product_id'] ?? 0);
                $qty    = (int) ($row['quantity'] ?? 0);
                $uPrice = (float) ($row['unit_price'] ?? 0);
                $tPrice = (float) ($row['total_price'] ?? ($uPrice * $qty));

                $p    = $products->get($pid);
                $name = $p?->name ?? '-';
                $desc = $p?->short_description ?? $p?->description ?? '';
                $thumb= $p?->thumbnail ?? null;

                $orderItems[] = [
                    'id'              => $pid,
                    'name'            => (string) $name,
                    'description'     => (string) $desc,
                    'thumbnail_image' => $thumb ? (str_starts_with($thumb, 'http') ? $thumb : url($thumb)) : url('/public/assets/img/placeholder.jpg'),
                    'price'           => ['amount' => $fmt($uPrice), 'symbol' => $symbol],
                    'quantity'        => $qty,
                ];
                $subTotal += $tPrice;
            }
        }

        // 5) تحديد "القسط الحالي": أول قسط غير مدفوع وتاريخه >= اليوم
        $now = Carbon::now();
        $firstUpcomingUuid = optional(
            $payments->first(function ($p) use ($now) {
                return $p->payment_status !== 'paid' && $p->due_date && Carbon::parse($p->due_date)->gte($now);
            })
        )->uuid;

        // قائمة الأقساط بالشكل المطلوب
        $paymentList = $payments->map(function ($p) use ($fmt, $symbol, $now, $firstUpcomingUuid) {
            $due = $p->due_date ? Carbon::parse($p->due_date) : null;

            // اسم الحالة العربية + السلاق
            [$nameAr, $slug] = (function() use ($p, $due, $now, $firstUpcomingUuid) {
                if ($p->payment_status === 'paid') return ['مدفوع', 'paid'];
                if ($due && $due->lt($now))       return ['مستحقة', 'outstanding'];
                if ($p->uuid === $firstUpcomingUuid) return ['مستحقة', 'outstanding'];
                return ['قيد الانتظار', 'pending'];
            })();

            return [
                'payment_id'         => (string) $p->uuid,
                'reference_id'       => (int) $p->id, // أرقام مثل العينة (10/11/12). بدّلها لو تبي مرجع آخر.
                'name_shop'          => '',
                'installment_number' => (int) $p->instalment_number,
                'current_installment'=> ($p->uuid === $firstUpcomingUuid),
                'date'               => $due ? $due->format('M d, Y') : null,
                'amount'             => ['amount' => $fmt($p->instalment_amount), 'symbol' => $symbol],
                'late_fee'           => ['amount' => $fmt($p->late_fee),          'symbol' => $symbol],
                'status'             => ['name' => $nameAr, 'slug' => $slug],
            ];
        })->values();

        // 6) ملخص الفاتورة
        $shippingCost   = (float) ($order->shipping_cost ?? 0);
        $couponDiscount = (float) ($order->coupon_discount ?? 0);
        $grandTotal     = (float) ($order->grand_total ?? ($subTotal + $shippingCost - $couponDiscount));

        // استخدم قيم transaction عند التوفر
        $collected = isset($tx->collected) ? (float) $tx->collected : (float) SchedulePayment::where('transaction_id', $uuid)
            ->where('payment_status', 'paid')
            ->sum(DB::raw('CASE WHEN COALESCE(deducted_amount,0) > 0 THEN deducted_amount ELSE instalment_amount END'));

        $retrieved = (float) ($tx->retrieved ?? 0);
        $canceled  = (float) ($tx->canceled  ?? 0);

        $remaining = max(0.0, $grandTotal - $collected + $retrieved + $canceled);

        // إجماليات الرسوم
        $totalLateFee = (float) SchedulePayment::where('transaction_id', $uuid)->sum('late_fee');
        // خذ الرسوم الاشتراكية من transaction إن وُجدت، وإلا اجمع من الأقساط
        $totalSubs    = isset($tx->subscription_fees) && $tx->subscription_fees !== null
            ? (float) $tx->subscription_fees
            : (float) SchedulePayment::where('transaction_id', $uuid)->sum('subscription_fee');

        // 7) مخرجات الهوية والتواريخ
        $idOrder   = $order->code ?: ($order->reference_id ?: ('AP-' . (10000 + (int)$order->id)));
        $dateOrder = optional($order->created_at)->format('M d, Y');

        $payload = [
            'id_order'    => (string) $idOrder,
            'date_order'  => (string) $dateOrder,
            'shop_name'   => $shopName,
            'payment_list'=> $paymentList,
            'order_items' => $orderItems,
            'bill_details'=> [
                'total'                  => ['amount' => $fmt($grandTotal), 'symbol' => $symbol],
                'collected'              => ['amount' => $fmt($collected),  'symbol' => $symbol],
                'retrieved'              => ['amount' => $fmt($retrieved),  'symbol' => $symbol],
                'canceled'               => ['amount' => $fmt($canceled),   'symbol' => $symbol],
                'remaining'              => ['amount' => $fmt($remaining),  'symbol' => $symbol],
                'total_late_fee'         => ['amount' => $fmt($totalLateFee), 'symbol' => $symbol],
                'total_subscription_fees'=> ['amount' => $fmt($totalSubs),    'symbol' => $symbol],
            ],
        ];

        return $this->returnData($payload, 'Payment details fetched successfully.');
    }



    public function updateProfile(Request $request)
    {
        $request->validate([
        'email' => 'required|email|unique:users,email,' . Auth::id(),
        ]);

        $user = Auth::user();
        $user->email = $request->email;
        $user->save();

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => 'Email updated successfully.',
        ]);
    }

    protected function getStatusName($slug)
    {
        return match ($slug) {
            'paid' => 'مدفوع',
            'outstanding' => 'مستحقة',
            'pending' => 'قيد الانتظار',
            default => 'غير معروف',
        };
    }

}