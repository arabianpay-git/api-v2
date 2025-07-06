<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use DB;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

class AuthController extends Controller
{
    const DUMMY_PHONE = '0551011969';
    const DUMMY_CODE = '444444';
    const MAX_ATTEMPTS = 5;
    const MAX_SENDS = 3;
    const COOLDOWN_SECONDS = 60;
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
}
