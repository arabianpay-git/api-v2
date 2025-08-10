<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\SchedulePayment;
use App\Models\UserCardToken;
use App\Services\ClickpayService;
use DB;
use Illuminate\Http\Request;
use Str;

class ClickpayController extends Controller
{
    public function __construct(private ClickpayService $cp) {}

    // 1) إنشاء دفعة أولى لتوليد توكن (CIT)
    public function initiateToken(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.5',
        ]);

        $amount = (float) $request->amount;
        $cartId = 'cart_'.uniqid();

        $payload = [
            'tran_type'        => 'sale',
            'tran_class'       => 'ecom',
            'cart_id'          => $cartId,
            'cart_currency'    => 'SAR',
            'cart_amount'      => $amount,
            'cart_description' => 'Initial payment to create unscheduled token',
            'return'           => route('clickpay.return'),
            'callback'         => route('clickpay.ipn'),
            'hide_shipping'    => true,
            'token_info'       => [
                'tokenise'   => '2',
                'token_type' => 'unscheduled',
                'counter'    => 999,
                'total_count'=> 999,
            ],
            'customer_details' => [
                'name'    => $request->user()?->name ?? 'Guest',
                'email'   => $request->user()?->email ?? 'guest@example.com',
                'country' => 'SA',
            ],
        ];

        $res = $this->cp->paymentRequest($payload);

        return response()->json($res->json(), $res->status());
    }

    // 2) IPN (Webhook) من ClickPay - عام بدون Auth
    public function ipn(Request $request)
    {
        $p = $request->all();

        // 1) تحديد الـ UUID الذي أرسلته في الطلب السابق:
        // الأفضل أن تجعل cart_id = نفس UUID للقسط، أو ترسله في user_defined.udf1
        $uuid = $p['cart_id'] ?? ($p['user_defined']['udf1'] ?? null);

        // قيَم أساسية من IPN
        $tranRef   = $p['tran_ref'] ?? null;
        $amount    = isset($p['cart_amount']) ? (float) $p['cart_amount'] : null;
        $currency  = $p['cart_currency'] ?? 'SAR';
        $resp      = $p['payment_result'] ?? [];
        $statusRaw = $resp['response_status'] ?? null;   // A/D/P...
        $code      = $resp['response_code'] ?? null;
        $message   = $resp['response_message'] ?? null;
        $token     = $p['token'] ?? null;

        // حوّل حالة ClickPay إلى حالة جدولك
        $mappedStatus = match ($statusRaw) {
            'A' => 'paid',
            'P' => 'pending',
            'H' => 'on_hold',
            'E' => 'error',
            'D', 'C' => 'failed', // Declined/Cancelled
            default => 'failed',
        };

        // احتراز: إن مافي UUID، سجّل المعاملة فقط وارجع OK
        if (!$uuid || !Str::isUuid($uuid)) {
            PaymentTransaction::create([
                'gateway'           => 'clickpay',
                'gateway_tran_ref'  => $tranRef,
                'amount'            => $amount,
                'currency'          => $currency,
                'status'            => $statusRaw,
                'result_code'       => $code,
                'result_message'    => $message,
                'payload'           => $p,
            ]);
            return response('OK', 200);
        }

        DB::transaction(function () use ($uuid, $tranRef, $amount, $currency, $statusRaw, $mappedStatus, $code, $message, $token, $p) {

            // 2) اقفل صف القسط المطلوب
            $sp = SchedulePayment::where('uuid', $uuid)->lockForUpdate()->first();

            // لو لم يوجد: سجّل فقط وانهِ
            if (!$sp) {
                PaymentTransaction::create([
                    'schedule_uuid'     => $uuid,
                    'gateway'           => 'clickpay',
                    'gateway_tran_ref'  => $tranRef,
                    'amount'            => $amount,
                    'currency'          => $currency,
                    'status'            => $statusRaw,
                    'result_code'       => $code,
                    'result_message'    => $message,
                    'payload'           => $p,
                ]);
                return;
            }

            // 3) امنع التكرار: إن كانت العملية موجودة بنفس tran_ref لا تُكرر
            $exists = PaymentTransaction::where('gateway', 'clickpay')
                        ->where('gateway_tran_ref', $tranRef)->exists();
            if ($exists) {
                // قد يكون Retry من ClickPay — لا تعدّل مرة أخرى
                return;
            }

            // 4) حدّث القسط حسب النتيجة
            // - transaction_id: خزّن tran_ref (أو transaction_id لو توفر)
            // - deducted_amount: المبلغ المقتطع فعليًا (إن نجحت)
            // - payment_status: حسب التحويل أعلاه
            $sp->transaction_id  = $tranRef ?: $sp->transaction_id;
            if ($statusRaw === 'A' && $amount !== null) {
                $sp->deducted_amount = $amount;
            }
            $sp->payment_status  = $mappedStatus;
            $sp->save();

            // 5) سجّل العملية (Log)
            PaymentTransaction::create([
                'schedule_payment_id'=> $sp->id,
                'schedule_uuid'      => $sp->uuid,
                'user_id'            => $sp->user_id,
                'order_id'           => $sp->order_id,
                'gateway'            => 'clickpay',
                'gateway_tran_ref'   => $tranRef,
                'token'              => $token,
                'amount'             => $amount,
                'currency'           => $currency,
                'status'             => $statusRaw,     // نخزن الخام من ClickPay
                'result_code'        => $code,
                'result_message'     => $message,
                'payload'            => $p,
            ]);
        });

        return response('OK', 200);
    }


    // 3) صفحة عودة العميل (اختياري للـ API)
    public function return(Request $request)
    {
        return response()->json(['message' => 'Return received', 'data' => $request->all()]);
    }

    // 4) سحب لاحق باستخدام التوكن (MIT / recurring)
    public function chargeWithToken(Request $request)
    {
        $request->validate([
            'user_id' => 'required|int',
            'amount'  => 'required|numeric|min:0.5',
        ]);

        $card = UserCardToken::where('user_id', $request->user_id)->latest()->first();
        if (!$card) {
            return response()->json(['ok' => false, 'error' => 'No saved token'], 422);
        }

        $payload = [
            'tran_type'        => 'sale',
            'tran_class'       => 'recurring',
            'cart_id'          => 'rb_'.uniqid(),
            'cart_currency'    => 'SAR',
            'cart_amount'      => (float) $request->amount,
            'cart_description' => 'Unscheduled recurring charge',
            'token'            => $card->token,
            'tran_ref'         => $card->tran_ref, // من عملية CIT
            'callback'         => route('clickpay.ipn'),
            'return'           => route('clickpay.return'),
            'token_info'       => [
                'tokenise'   => '2',
                'token_type' => 'unscheduled',
            ],
        ];

        $res = $this->cp->paymentRequest($payload);

        return response()->json([
            'ok'       => $res->successful(),
            'response' => $res->json(),
        ], $res->status());
    }
}
