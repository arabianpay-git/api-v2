<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

class AuthController extends Controller
{
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

        $phone = $request->phone;
        $otpInput = $request->otp;

        // Look up active OTP
        $otp = DB::table('otps')
            ->where('phone', $phone)
            ->where('code', $otpInput)
            ->where('used', 0)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            // Increment attempts for last unexpired OTP if exists
            DB::table('otps')
                ->where('phone', $phone)
                ->where('expires_at', '>', now())
                ->where('used', 0)
                ->increment('attempts');

            return response()->json(['message' => __('api.otp_invalid')], 422);
        }

        // Mark OTP as used
        DB::table('otps')->where('id', $otp->id)->update([
            'used' => 1,
            'updated_at' => now(),
        ]);

        // Find or create user by phone
        $user = User::firstOrCreate(
            ['phone_number' => $phone],
            [
                'first_name' => 'User',
                'last_name' => 'Name',
                'email' => uniqid('user_')."@example.com",
                'password' => Hash::make(str()->random(16)),
            ]
        );

        // Issue Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => __('api.otp_verified'),
            'token' => $token,
            'user' => $user,
        ]);
    }
}
