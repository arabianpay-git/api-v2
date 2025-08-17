<?php

namespace App\Http\Controllers;

use App\Models\AdsSlider;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopSetting;
use App\Models\Slider;
use Auth;
use DB;
use Http;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Log;
use Str;

class PaymentsController extends Controller
{
    public function createPayment(Request $request)
    {
        Log::debug('Creating payment with request: ', $request->all());
        

        $user = auth()->user();
        $order = Order::findOrFail($request->order_id);

        // Determine amount (e.g., for the whole order or next installment)
        $clickpayResponse = $this->createClickPayInvoice($order, $user);

        if (isset($clickpayResponse['error']) && $clickpayResponse['error']) {
            return response()->json([
                'status' => false,
                'errNum' => 'E500',
                'msg' => 'Payment initialization failed: ' . $clickpayResponse['message'],
                'details' => $clickpayResponse['details'],
            ], 500);
        }

        $referenceId = $clickpayResponse['tran_ref'] ?? null;

        if (!$referenceId) {
            return response()->json([
                'status' => false,
                'errNum' => 'E500',
                'msg' => 'Payment initialization failed: missing transaction reference.',
                'details' => $clickpayResponse,
            ], 500);
        }

        $transactionRef = Str::uuid()->toString();

        $amount = (float) $order->grand_total;

        // Save transaction record (optional but recommended)
        \App\Models\Transaction::create([
            'uuid' => $transactionRef,
            'user_id' => auth()->id(),
            'seller_id' => $order->seller_id,
            'order_id' => $order->id,
            'loan_amount' => $amount,
            'payment_status' => 'pending',
            'refrence_payment' => $referenceId ?? 'N/A', // <= add this!
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => 'Payment created successfully.',
            'payment_url' => $clickpayResponse['redirect_url'],
        ]);
    }


    protected function createClickPayInvoice($order, $user)
    {
        $clickPayBaseUrl = config("paytabs.url"); // e.g., https://secure.clickpay.com
        $apiKey = env('PROFILE_SERVER_KEY');
        $profileId = env('PROFILE_ID');

        $callbackUrl = route('clickpay.callback');

        $payload = [
            'profile_id' => $profileId,
            'tran_type' => 'sale',
            'tran_class' => 'ecom',
            'cart_id' => 'order_' . $order->id,
            'cart_description' => 'Order #' . $order->id,
            'cart_currency' => 'SAR',
            'cart_amount' => $order->grand_total,
            'customer_details' => [
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone_number,
                'street1' => $order->shipping_address_line1,
                'city' => $order->shipping_city,
                'state' => $order->shipping_state,
                'country' => 'SA',
                'zip' => $order->shipping_postal_code ?? '00000',
            ],
            'callback' => $callbackUrl,
            'return' => $callbackUrl,
        ];

        $response = Http::withHeaders([
            'authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post("$clickPayBaseUrl/payment/request", $payload);

        $responseJson = $response->json();

        if ($response->failed() || isset($responseJson['code']) || !isset($responseJson['tran_ref'])) {
            // ClickPay-specific error, or no tran_ref returned
            return [
                'error' => true,
                'message' => $responseJson['message'] ?? 'Unknown ClickPay error',
                'details' => $responseJson,
            ];
        }

        return $responseJson;
    }

}