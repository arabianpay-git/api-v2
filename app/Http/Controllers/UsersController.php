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
        $dashboardSlider = AdsSlider::take(10)->get()->map(function ($item) {
            return [
                'image' => $item->image?? 'https://api.arabianpay.com/uploads/sliders/default_cover.png',
                'image_id' => (string) $item->id,
                'target' => [
                    'type' => 'null',
                    'id' => 0,
                    'name' => 'NuN',
                    'image' => '',
                    'rating' => 0
                ]
            ];
        });

        $topDealSlider = $dashboardSlider; // أو اجلبها من جدول آخر إن أردت

        $adBannerOne = $dashboardSlider->take(1); // أو خصصها من جدول آخر أو شرط معين
        $topStore = ShopSetting::get()->map(function ($shop) {
            return [
                "id" => $shop->id,
                "slug" => $shop->slug,
                "user_id" => $shop->user_id,
                "name" => $shop->name,
                "logo" => $shop->logo??'https://api.arabianpay.com/uploads/shops/default_cover.png',
                "cover" => $shop->cover??'https://api.arabianpay.com/uploads/shops/default_cover.png',
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
            'business_name' => $user->business_name,
            'email' => $user->email,
            'id_number' => $user->iqama,    // assuming iqama is your id_number
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