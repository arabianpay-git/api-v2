<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\SchedulePayment;
use App\Models\UserCards;
use Arr;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Http;

class ClickpayService
{
    protected string $baseUrl;
    protected string $serverKey;
    protected int $profileId;
    /**
     * احفظ بيانات الدفع والبطاقة من Response كلك باي.
     *
     * @param  array       $payload   // JSON decoded response
     * @param  int         $userId
     * @param  int|null    $sellerId  // لو الدفع مرتبط ببائع محدد
     * @param  string|null $orderId   // لو عندك رقم طلب داخلي مختلف عن cartID
     * @param  string|null $invoiceNo // رقم فاتورة داخلي إن وجد
     * @param  string|null $taxNo     // الرقم الضريبي إن وجد
     * @return array [user_card => UserCard, payment => Payment]
     */

    public function __construct()
    {
        //$this->baseUrl   = config('services.clickpay.base_url');
        //$this->serverKey = config('services.clickpay.server_key');
        //$this->profileId = (int) config('services.clickpay.profile_id');
    }

    protected function client()
    {
        return Http::withHeaders([
            'authorization' => $this->serverKey,
            'content-type'  => 'application/json',
        ])->baseUrl($this->baseUrl);
    }

    public function paymentRequest(array $payload)
    {
        $payload['profile_id'] = $payload['profile_id'] ?? $this->profileId;
        return $this->client()->post('/payment/request', $payload);
    }

    public function storeFromClickpay(array $payload, int $userId, ?int $sellerId = null, ?string $orderId = null, ?string $invoiceNo = null, ?string $taxNo = null): array
    {
        return DB::transaction(function () use ($payload, $userId, $sellerId, $orderId, $invoiceNo, $taxNo) {

            // --- 1) تجهيز قيم البطاقة ---
            $token         = Arr::get($payload, 'token'); // مهم للتفادي التكرار
            $cardScheme    = Arr::get($payload, 'paymentInfo.cardScheme');  // Visa/Master...
            $cardType      = Arr::get($payload, 'paymentInfo.cardType');    // Credit/Debit
            $expMonth      = (string) Arr::get($payload, 'paymentInfo.expiryMonth');
            $expYear       = (string) Arr::get($payload, 'paymentInfo.expiryYear');
            $maskedNumber  = (string) Arr::get($payload, 'paymentInfo.paymentDescription'); // "4458 27## #### 6490"

            // هل عند المستخدم بطاقة سابقة Default؟
            $alreadyHasDefault = UserCards::where('user_id', $userId)->where('is_default', 1)->exists();

            // نحفظ/نحدث البطاقة حسب الـ token + user_id (تفادي التكرار)
            /** @var UserCards $userCard */
            $userCard = UserCards::updateOrCreate(
                ['user_id' => $userId, 'token' => $token],
                [
                    'type'         => $cardType,
                    'scheme'       => $cardScheme,
                    'number'       => $maskedNumber,
                    'expiry_year'  => $expYear,
                    'expiry_month' => $expMonth,
                    'is_default'   => $alreadyHasDefault ? 0 : 1, // أول بطاقة؟ خلّها افتراضية
                ]
            );

            // --- 2) تجهيز قيم الدفع ---
            $uuid              = Arr::get($payload, 'transactionReference');           // مثال: SFT2523132046612
            $amount            = (string) Arr::get($payload, 'tran_total', Arr::get($payload, 'cartAmount'));
            $txnCode           = Arr::get($payload, 'paymentResult.responseCode');     // مثال: 013197
            $statusCode        = Arr::get($payload, 'paymentResult.responseStatus');   // A/H/P/...
            $statusMessage     = Arr::get($payload, 'paymentResult.responseMessage');  // Authorised
            $cartIdFromGateway = Arr::get($payload, 'cartID');                         // إن كنت تعتمد uuid للطلب
            $transactionTime   = Arr::get($payload, 'paymentResult.transactionTime');  // ISO8601

            // اختر order_id: المرسل للدالة أو cartID من البوابة
            $finalOrderId = $orderId ?: $cartIdFromGateway;

            $status      = strtoupper((string) Arr::get($payload, 'paymentResult.responseStatus', ''));
            $isSuccess   = (bool) Arr::get($payload, 'isSuccess', false);
            $isProcessed = (bool) Arr::get($payload, 'isProcessed', false);
            $txnType     = strtolower((string) Arr::get($payload, 'transactionType', '')); // sale | auth

            // حوّل كود الحالة لنص داخلي (اختياري)
            $paymentStatus =match ($status) {
                'A' => ($txnType === 'sale' || $isProcessed || $isSuccess) ? 'paid' : 'pending',
                'S' => 'paid',
                'H', 'P' => 'pending',
                'D', 'C', 'V' => 'failed',
                default => $isSuccess ? 'paid' : 'failed',
            };

            // جهّز تفاصيل الدفع كـ JSON (اختصر/أضف ما يلزمك)
            $paymentDetails = [
                'gateway'          => 'clickpay',
                'message'          => $statusMessage,
                'status_code'      => $statusCode,
                'transaction_time' => $transactionTime,
                'currency'         => Arr::get($payload, 'tran_currency', Arr::get($payload, 'cartCurrency')),
                'payment_info'     => Arr::get($payload, 'paymentInfo'),
                'billing'          => Arr::get($payload, 'billingDetails'),
                'shipping'         => Arr::get($payload, 'shippingDetails'),
                'trace'            => Arr::get($payload, 'trace'),
                'merchantId'       => Arr::get($payload, 'merchantId'),
                'profileId'        => Arr::get($payload, 'profileId'),
                'serviceId'        => Arr::get($payload, 'serviceId'),
                'isSuccess'        => Arr::get($payload, 'isSuccess'),
                'raw'              => $payload, // مفيد للدعم لاحقاً
            ];

            // رقم الفاتورة: المرسل أو نفس الـ uuid
            $finalInvoice = $invoiceNo ?: $uuid;

            /** @var Payment $payment */
            $payment = Payment::create([
                'uuid'            => $uuid,
                'user_id'         => $userId,
                'seller_id'       => $sellerId,
                'order_id'        => $finalOrderId,
                'amount'          => $amount,
                'payment_details' => json_encode($paymentDetails, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), // JSON (تأكد من الـ cast)
                'invoice_number'  => $finalInvoice,
                'txn_code'        => $txnCode,
                'tax_number'      => $taxNo,
                'payment_status'  => $paymentStatus,
                'created_at'      => $transactionTime ? Carbon::parse($transactionTime) : now(),
                'updated_at'      => now(),
            ]);

            $schedule_payment = SchedulePayment::where('order_id', $finalOrderId)->where('payment_status','due')->first();
            if($schedule_payment) {
                $schedule_payment->payment_status = 'paid';
                $schedule_payment->save();
            }
            return [
                'user_card' => $userCard,
                'payment'   => $payment,
            ];
        });
    }
}
