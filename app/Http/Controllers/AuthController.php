<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use DB;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use Str;

class AuthController extends Controller
{
    const DUMMY_PHONE = '0551011969';
    const DUMMY_CODE = '444444';
    const MAX_ATTEMPTS = 5;
    const MAX_SENDS = 3;
    const COOLDOWN_SECONDS = 60;

    const AUTHORIZE_ENDPOINT = '/nafath/api/v1/client/authorize/';
    const STATUS_ENDPOINT = 'https://api.arabianpay.co/api/v1/check-nafath-status';
    public function requestOtp(Request $request)
    {
        $request->validate(['phone' => 'required|string|regex:/^\d{9,15}$/']);
        $phone = $request->phone;

        // Generate 6-digit OTP
        $otpCode = rand(100000, 999999);

        // Check for existing active OTP for the phone
        $existingOtp = DB::table('otps')
            ->where('phone', $phone)
            ->where('used', 0)
            ->where('expires_at', '>', now())
            ->first();

        if ($existingOtp) {
            // If an unexpired OTP already exists, you could optionally resend the same OTP,
            // or just increment the sends count
            DB::table('otps')->where('id', $existingOtp->id)->increment('sends');
            logger("OTP reused for {$phone}: {$existingOtp->code}");

            return response()->json(['message' => __('api.otp_sent')]);
        }

        // Insert new OTP
        DB::table('otps')->insert([
            'phone' => $phone,
            'code' => $otpCode,
            'attempts' => 0,
            'sends' => 1,
            'used' => 0,
            'expires_at' => Carbon::now()->addMinutes(5),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Log OTP for development/debugging (replace with SMS gateway in production)
        logger("OTP generated for {$phone}: {$otpCode}");

        return response()->json(['message' => __('api.otp_sent')]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^\d{9,15}$/',
            'otp'   => 'required|digits:6',
        ]);

            // Dummy login shortcut for local/testing
        if (env('APP_ENV') === 'local' 
            && $request->phone === self::DUMMY_PHONE 
            && $request->otp === self::DUMMY_CODE) {

            $user = User::whereEncrypted('phone_number', $request->phone)->first();

            if (! $user) {
                return response()->json([
                    'status' => false,
                    'errNum' => 'E404',
                    'msg' => 'Dummy user not found in database.',
                ], 404);
            }

            Auth::login($user);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => true,
                'errNum' => 'S200',
                'msg' => __('api.otp_verified'),
                'token' => $token,
                'user' => $user,
            ]);
        }
    }

    public function requestOtpRegister(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^\d{9,15}$/',
            'id_number' => 'required|string|min:10|max:15',
        ]);

        $code = rand(100000, 999999); 
        $expiresAt = now()->addMinutes(5);

        DB::table('otps')->updateOrInsert(
            ['phone' => $request->phone],
            [
                'code' => $code,
                'identity' => $request->id_number,
                'used' => 0,
                'attempts' => 0,
                'sends' => DB::raw('sends + 1'),
                'expires_at' => $expiresAt,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        // TODO: send SMS via your service

        return response()->json([
            'status' => true,
            'msg' => 'تم إرسال رمز التحقق بنجاح.',
        ]);
    }

    public function verifyRegistration(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^\d{9,15}$/',
            'otp' => 'required|digits:6',
        ]);

        $otp = DB::table('otps')
            ->where('phone', $request->phone)
            ->where('code', $request->otp)
            ->where('used', 0)
            ->where('expires_at', '>', now())
            ->first();

        if (! $otp) {
            DB::table('otps')
                ->where('phone', $request->phone)
                ->increment('attempts');

            return response()->json([
                'status' => false,
                'errNum' => 'E400',
                'msg' => 'رمز التحقق غير صحيح أو منتهي.',
            ], 422);
        }

        // تحقق من الهوية باستخدام بيانات otp->identity
        $idNumber = $otp->identity;

        // TODO: تحقق من الهوية عبر نفاذ أو أي نظام خارجي
        return $this->callCheckStatusCurl($idNumber,$request->phone);
        // if (! Nafath::verify($idNumber)) { ... }


        DB::table('otps')->where('id', $otp->id)->update(['used' => 1]);

        $user = User::firstOrCreate(
            ['phone_number' => $request->phone],
            [
                'first_name' => 'User',
                'last_name' => 'Name',
                'email' => uniqid('user_').'@example.com',
                'password' => bcrypt(Str::random(16)),
                'identity' => $idNumber,
            ]
        );

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => 'تم التحقق من المستخدم وتسجيله.',
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function simulateNafathResponse(Request $request)
    {
        $request->validate([
            'response' => 'required|array',
        ]);

        $response = $request->response;

        $identityNumber = $response['id'] ?? null;

        if (!$identityNumber) {
            return response()->json([
                'status' => false,
                'errNum' => 'E422',
                'msg' => 'Identity number missing from response.',
            ], 422);
        }

        // التحقق أو إنشاء المستخدم
        $user = User::firstOrCreate(
            ['identity_number' => $identityNumber],
            [
                'first_name' => $response['first_name#ar'] ?? '',
                'last_name' => $response['family_name#ar'] ?? '',
                'email' => uniqid('nafath_') . '@example.com',
                'password' => bcrypt(str()->random(16)),
            ]
        );

        // إصدار التوكن
        $token = $user->createToken('nafath_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => 'User verified successfully via Nafath.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'identity_number' => $user->identity_number,
            ],
        ]);
    }

    public function callCheckStatusCurl(string $idNumber, string $phone): array
    {
        $payload = json_encode([
            'id_number' => $idNumber,
            'phone' => $phone,
        ]);

        $headers = [
            'Authorization: apikey 62976ae5-35b3-4e73-8e3e-b0e40d2b2d29',
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.arabianpay.co/api/v1/check-nafath-status', // Update to actual route
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);

            return [
                'success' => false,
                'message' => 'CURL request failed',
                'error' => $error,
            ];
        }

        curl_close($ch);
        $data = json_decode($response, true);

        return [
            'success' => $httpCode === 200,
            'status_code' => $httpCode,
            'data' => $data,
        ];
    }


}
