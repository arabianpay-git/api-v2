<?php

namespace App\Http\Controllers;

use App\Models\AdsSlider;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CustomerCreditLimit;
use App\Models\Merchant;
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
                    "transaction_id" => $tx->id,
                    "reference_id" => $tx->reference_id,
                    "name_shop" => $tx->store->name ?? '',
                    "schedule_payments" => $tx->schedulePayments->map(function ($sp) {
                        return [
                            "payment_id" => $sp->id,
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
        $dashboardSlider = AdsSlider::take(10)->orderBy('id','DESC')->get()->map(function ($item) {
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

        $data2 = "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";

        return $this->returnData($data2);
    }


    public function getInfo(Request $request)
    {
        $user = Auth::user();

        $data = [
            'id' => $user->id,
            'name' => $user->first_name . ' ' . $user->last_name,
            'business_name' => $user->business_name??'-',
            'email' => $user->email,
            'id_number' => $user->iqama??'-',    // assuming iqama is your id_number
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

        // Load payments with their transaction and merchant/shop relationship
        $payments = SchedulePayment::with(['transaction', 'transaction.shop'])
            ->where('user_id', $userId)
            ->get()
            ->groupBy('transaction_id')
            ->map(function ($groupedPayments, $transactionId) {
                $transaction = $groupedPayments->first()->transaction;

                return [
                    'transaction_id' => $transactionId,
                    'reference_id' => $transaction->reference_id ?? '',
                    'name_shop' => $transaction->shop->name ?? '',

                    'schedule_payments' => $groupedPayments->map(function ($payment) {
                        return [
                            'payment_id' => $payment->id,
                            'reference_id' => $payment->reference_id,
                            'name_shop' => '', // optional – can omit or fill
                            'installment_number' => $payment->installment_number,
                            'current_installment' => $payment->start_date && $payment->due_date
                                                    ? now()->between($payment->start_date, $payment->due_date)
                                                    : false,
                            'date' => \Carbon\Carbon::parse($payment->due_date)->translatedFormat('M d, Y'),

                            'amount' => [
                                'amount' => number_format($payment->amount, 2),
                                'symbol' => 'SR',
                            ],
                            'late_fee' => [
                                'amount' => number_format($payment->late_fee ?? 0, 2),
                                'symbol' => 'SR',
                            ],
                            'status' => [
                                'name' => $this->getStatusName($payment->status), // translate status
                                'slug' => $payment->status,
                            ],
                        ];
                    })->values(),
                ];
            })
            ->values();

            return $this->returnData($payments);

    }

    public function getSpent(Request $request)
    {
        $data = [
            'status' => true,
            'errNum' => 'S200',
            'msg' => 'Spending stats fetched successfully.',
            'data' => [
                'credit_limit' => [
                    'amount' => 5000,
                    'currency' => 'SAR',
                ],
                'total_spent' => [
                    'amount' => 1500,
                    'currency' => 'SAR',
                ],
                'total_cate_purchases' => [
                    [
                        'category_id' => 1,
                        'category_name' => 'Electronics',
                        'amount' => 800,
                        'currency' => 'SAR',
                    ],
                    [
                        'category_id' => 2,
                        'category_name' => 'Clothing',
                        'amount' => 700,
                        'currency' => 'SAR',
                    ],
                ],
                'monthly_spending_stats' => [
                    [
                        'date' => '2025-06',
                        'spent' => [
                            'amount' => 1000,
                            'currency' => 'SAR',
                        ]
                    ],
                    [
                        'date' => '2025-07',
                        'spent' => [
                            'amount' => 500,
                            'currency' => 'SAR',
                        ]
                    ]
                ],
                'top_store' => [
                    [
                        'store_id' => 5,
                        'store_name' => 'Extra',
                        'amount_spent' => 600,
                        'currency' => 'SAR',
                    ],
                    [
                        'store_id' => 9,
                        'store_name' => 'Jarir',
                        'amount_spent' => 400,
                        'currency' => 'SAR',
                    ]
                ]
            ]
        ];

        return $this->returnData($data);

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

    public function getPaymentDetails(Request $request, $uuid)
    {
        // Fetch payment details by UUID
        $payment = SchedulePayment::where('uuid', $uuid)->first();

        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        return $this->returnData($payment);
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