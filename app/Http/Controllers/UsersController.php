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
        $totalDue = $schedulePayments->sum('instalment_amount');   // Replace this with your own logic
        $totalPaid = SchedulePayment::where('payment_status','paid')->sum('instalment_amount');   // Replace this with your own logic
        $limit = CustomerCreditLimit::where('user_id',$userId)->sum('limit_arabianpay_after');     // Replace this with your own logic

        $paymentDueSoon = [
            [
                "transaction_id" => "xxx-uuid",
                "reference_id" => "AP-10001",
                "name_shop" => "Sample Shop",
                "schedule_payments" => [
                    $schedulePayments
                ]
            ]
        ];

        $dashboardSlider = []; // Collect your sliders from DB
        $topDealSlider = [];   // Collect top deals from DB
        $adBannerOne = [];     // Collect banners from DB
        $topStore = [];        // Collect top stores from DB

        return response()->json([
            'total_order' => $totalOrder,
            'total_due' => ['amount' => number_format($totalDue, 2), 'symbol' => 'SR'],
            'total_paid' => ['amount' => number_format($totalPaid, 2), 'symbol' => 'SR'],
            'limit' => ['amount' => number_format($limit, 2), 'symbol' => 'SR'],
            'payment_due_soon' => $paymentDueSoon,
            'dashboard_slider' => $dashboardSlider,
            'top_deal_slider' => $topDealSlider,
            'ad_banner_one' => $adBannerOne,
            'top_store' => $topStore,
        ]);
    }

    public function getInfo(Request $request)
    {
        $user = Auth::user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->first_name . ' ' . $user->last_name,
            'business_name' => $user->business_name,
            'email' => $user->email,
            'id_number' => $user->iqama,    // assuming iqama is your id_number
            'phone' => $user->phone_number,
            'token' => null, // because you're using Sanctum tokens separately
            'complete' => 1, // or check from user profile completeness if needed
            'package' => null, // or fetch user's subscription if applicable
        ]);
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

        return response()->json($payments);
    }

    public function getSpent(Request $request)
    {
        return response()->json([
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
        ]);

    }
    public function getCards(Request $request)
    {
        // Assuming you have a method to fetch user's cards
        $userId = $request->user()->id;
        $cards = []; // Fetch user's cards from the database

        return response()->json([
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
                            ]);

    }

    public function getPaymentDetails(Request $request, $uuid)
    {
        // Fetch payment details by UUID
        $payment = SchedulePayment::where('uuid', $uuid)->first();

        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        return response()->json($payment);
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