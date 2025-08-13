<?php

namespace App\Http\Controllers;

use App\Helpers\EncryptionService;
use App\Models\Customer;
use App\Models\NafathVerification;
use App\Models\Otp;
use App\Services\NafathService;
use Auth;
use Carbon\Carbon;
use DB;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Log;
use Str;
use App\Traits\ApiResponseTrait;
use Validator;

class AuthController extends Controller
{
    use ApiResponseTrait;
    const DUMMY_PHONE = '966551011969';
    const DUMMY_CODE = '4444';
    const MAX_ATTEMPTS = 5;
    const MAX_SENDS = 3;
    const COOLDOWN_SECONDS = 60;

    const AUTHORIZE_ENDPOINT = '/nafath/api/v1/client/authorize/';
    const STATUS_ENDPOINT = 'https://api.arabianpay.co/api/v1/check-nafath-status';
    public function requestOtp(Request $request)
    {
        $encryptionService = new EncryptionService();
        $request->validate(['phone_number' => 'required|string']);
        
        // فك التشفير
        $phone = '966'.$encryptionService->decrypt($request->input('phone_number'));

        // Generate 6-digit OTP
        $otpCode = rand(1000, 9999);

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

            return $this->returnData([], __('api.otp_sent'));

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

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => __('api.otp_sent'),
        ]);
    }

    public function callbackNafath(Request $request){
        Log::info('Shadow webhook received a mirrored request.', [
            'method'  => $request->method(),
            'query'   => $request->query(),
            'headers' => $request->headers->all(),
            'body'    => json_decode($request->getContent(), true) ?? $request->all(),
            'files'   => array_keys($request->allFiles()),
        ]);

        return response()->json(['ok' => true], 200);
    }

    
    public function verifyOtp(Request $request)
    {
        $encryptionService = new EncryptionService();

        // Decrypt values from the request
        $phone = '966'.$encryptionService->decrypt($request->input('phone_number'));
        $otp = $encryptionService->decrypt($request->input('otp'));
        $notificationToken = $encryptionService->decrypt($request->input('notification_token'));

        // Validate decrypted data
        $validated = Validator::make([
            'phone_number' => $phone,
            'otp' => $otp,
        ], [
            'phone_number' => 'required|regex:/^\d{9,15}$/',
            'otp' => 'required|digits:4',
        ])->validate();

        $phone055 = $this->denormalizePhoneNumber($phone);
        $phoneNorm = $this->normalizePhoneNumber($phone);

        // Dummy shortcut for development testing
        if (env('APP_ENV') === 'local' && $phone === self::DUMMY_PHONE && $otp === self::DUMMY_CODE) {
            $user = User::where('phone_number', $encryptionService->db_encrypt($phone055))
            ->orWhere('phone_number', $encryptionService->db_encrypt($phoneNorm))
            ->first();
            if (! $user) {
                return response()->json([
                    'status' => false,
                    'errNum' => 'E404',
                    'msg' => 'Dummy user not found in database.',
                ], 404);
            }

            Auth::login($user);
            $token = $user->createToken('auth_token')->plainTextToken;

            $data = ["id" => $user->id,
                    "name" => $user->first_name . ' ' . $user->last_name,
                    "business_name" => $user->business_name,
                    "email" => $user->email,
                    "id_number" => $user->identity,
                    "phone" => $user->phone_number,
                    "token" => $token,
                    "complete" => 1,
                        "package" => [
                            "slug" => $user->package->slug ?? 'free',
                            "name" => $user->package->name ?? 'Free Package',
                            "logo" => $user->package->logo ?? url('/assets/img/placeholder.jpg'),
                        ]
                    ];
                
            return $this->returnData($data, __('api.otp_verified'));
        }

        // ✅ Example for real OTP verification (production)
        $otpRecord = Otp::where('phone', $phoneNorm)->where('code', $otp)->where('used', 0)->first();
        if (! $otpRecord) {
            return $this->returnError('Invalid or expired OTP.', 'E401');
        }

        // Mark OTP as used
        $otpRecord->update(['used' => 1]);

        $user = User::where('phone_number', $phoneNorm)->first();
        if (! $user) {
            return $this->returnError('User not found.', 'E404');
        }

        $user->notification_token = $notificationToken;
        $user->save();

        Auth::login($user);
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->returnData('data', [
            'token' => $token,
            'user' => $user,
        ], __('api.otp_verified'));
    }


    public function requestOtpRegister(Request $request)
    {
        $encryptionService = new EncryptionService();

        // فك التشفير
        $phone = $encryptionService->decrypt($request->input('phone'));
        $idNumber = $encryptionService->decrypt($request->input('id_number'));

        // التحقق من صحة البيانات
        Validator::make([
            'phone' => $phone,
            'id_number' => $idNumber,
        ], [
            'phone' => 'required|regex:/^\d{9,15}$/|unique:users,phone_number',
            'id_number' => 'required|digits_between:5,20',
        ])->validate();

        
        if (User::where('phone_number', $encryptionService->db_encrypt($phone))
            ->orWhere('iqama',$encryptionService->db_encrypt($idNumber))->exists()) {
            return response()->json([
                'status' => false,
                'errNum' => 'E409',
                'msg' => 'User with this phone number already exists.',
            ], 409);
        }

        // إنشاء أو تحديث OTP
        $otpCode = rand(100000, 999999);

        Otp::updateOrCreate(
            ['phone' => $phone],
            [
                'id_number' => $idNumber,
                'code' => $otpCode,
                'expires_at' => now()->addMinutes(10),
                'used' => 0,
                'attempts' => DB::raw('attempts + 1'),
                'sends' => DB::raw('sends + 1'),
            ]
        );

        // يمكنك إرسال الـ OTP برسالة SMS، أو فقط تسجيله في الـ Log للتجربة
        Log::info('OTP for ' . $phone . ': ' . $otpCode);

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => 'OTP sent successfully. Complete registration after verification.',
        ]);
    }
    

    public function verifyRegistration(Request $request)
    {
        $encryptionService = new EncryptionService();

        // فك التشفير
        $phone = $encryptionService->decrypt($request->input('phone'));
        $otp = $encryptionService->decrypt($request->input('otp'));

        // التحقق من صحة الفورم
        Validator::make([
            'phone' => $phone,
            'otp' => $otp,
        ], [
            'phone' => 'required|regex:/^\d{9,15}$/',
            'otp' => 'required|digits:6',
        ])->validate();

        // تحقق من الـ OTP
        $otpRecord = Otp::where('phone', $phone)
            ->where('code', $otp)
            ->where('used', 0)
            ->where('expires_at', '>', now())
            ->first();

        if (! $otpRecord) {
            return response()->json([
                'status' => false,
                'errNum' => 'E401',
                'msg' => 'Invalid or expired OTP.',
            ], 401);
        }

        $otpRecord->update(['used' => 1]);

        // بعد نجاح OTP، نحصل على رقم الهوية
        $idNumber = $otpRecord->id_number;

        return $this->returnData(['id_number'=>$idNumber], 'OTP verified successfully.');
    }

    public function verifyWithNafath(Request $request, NafathService $nafath)
    {
        
        $encryptionService = new EncryptionService();
        $idNumber = $encryptionService->decrypt($request->input('id'));
        $phoneNumber = $encryptionService->decrypt($request->input('phone_number'));

        $count = NafathVerification::where('national_id', $idNumber)->count();

        if ($count < 10) {
            $response = $nafath->initiateVerification($idNumber);

            if (isset($response['status']) && str_starts_with($response['status'], '400-')) {
                return response()->json([
                    'status' => false,
                    'errNum' => 'E400',
                    'msg' => $response['message'] ?? 'Nafath Error',
                ], 400);
            }

            if (!isset($response['transId'], $response['random'])) {
                return response()->json([
                    'status' => false,
                    'errNum' => 'E422',
                    'msg' => 'Cannot start session, invalid Nafath response.',
                ], 422);
            }

            // إذا لم يكن هناك سجل موجود، نقوم بإنشاء سجل جديد
            // حفظ السجل في جدول nafath_verifications
            $nafathVerification = NafathVerification::create([
                'national_id'    => $idNumber,
                'iqama_hash'     => Hash::make($idNumber),
                'phone_number'   => $phoneNumber,
                'trans_id'       => $response['transId'],
                'random'         => $response['random'],
                'status'         => "pending",
                'nafath_response'=> json_encode($data['data'] ?? []),
            ]);

            $data = [
                "id" => $nafathVerification->id,
                "id_number" => $nafathVerification->national_id,
                "phone" => $nafathVerification->phone_number,
                "random" => $response['random'],
                "transId" => $response['transId'],
                "email_verified_at" => null,
                "try" => 5,
                "created_at" => $nafathVerification->created_at,
                "updated_at" => $nafathVerification->updated_at
            ];
            return $this->returnData($data, 'Nafath verification initiated successfully.');
        }else{
            return response()->json([
                'status' => false,
                'errNum' => 'E429',
                'msg' => 'Too many requests, please try again later.',
            ], 429);
        }
    }

    public function checkNafathStatus(Request $request, NafathService $nafath)
    {
        $request->validate([
            'id_number' => 'required|string',
            'trans_id' => 'required|string',
        ]);

        $encryptionService = new EncryptionService();
        $idNumber = $encryptionService->decrypt($request->input('id_number'));

        try {
            $response = $nafath->processCallback(
                $request->trans_id,
                $idNumber,
            );

            $data = $response['data'] ?? null;
            $status = $data ? 'approved' : 'error';

            // استعلام otp للحصول على الهاتف
            $otp = Otp::where('id_number', $idNumber)
                ->where('used', 1)->orderBy('created_at', 'desc')
                ->first();
            $phone = $otp ? $otp->phone : null;

            $encryptionService = new EncryptionService();

            // حفظ السجل في جدول nafath_verifications
            NafathVerification::create([
                'national_id'    => $idNumber,
                'iqama_hash'     => Hash::make($idNumber),
                'phone_number'   => $phone,
                'trans_id'       => $request->trans_id,
                'random'         => Str::random(2),
                'status'         => $status,
                'nafath_response'=> json_encode($data['data'] ?? []),
            ]);

            if (!$data) {
                return response()->json([
                    'status' => false,
                    'errNum' => 'E404',
                    'msg' => 'No data found for the provided ID number.',
                ], 404);
            }

            return $this->returnData([
                'id_number' => $encryptionService->db_encrypt($idNumber),
                'phone' => $phone,
                'nafath_data' => $data,
            ], 'Nafath verification completed successfully.');
          
        } catch (\Exception $e) {
            Log::error('Error processing Nafath: ' . $e->getMessage());

            NafathVerification::create([
                'national_id'    => $idNumber,
                'iqama_hash'     => Hash::make($idNumber),
                'phone_number'   => $phone ?? null,
                'trans_id'       => $request->trans_id,
                'random'         => Str::random(2),
                'status'         => 'error',
                'error_code'     => 'E500',
                'nafath_response'=> null,
                'verified_at'    => null,
            ]);

            return response()->json([
                'status' => false,
                'errNum' => 'E500',
                'msg' => 'Failed to process Nafath verification.',
            ], 500);
        }
    }

    public function createUserFromNafath(array $nafathData, string $phone)
    {
        $user = User::create([
            'first_name'   => $nafathData['first_name#en'] ?? '',
            'last_name'    => $nafathData['family_name#en'] ?? '',
            'user_type'    => 'user',
            'email'        => $phone . '@arabianpay.net', // Temporary email
            'business_name'=> null,
            'iqama'        => $nafathData['id'] ?? null,
            'phone_number' => $phone,
            'department_id'=> null,
            'is_manager'   => false,
            'country_id'   => null,
            'state_id'     => null,
            'city_id'      => null,
            'email_verified_at' => null,
            'password'     => Hash::make(Str::random(10)), // كلمة مرور مؤقتة
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'remember_token' => null,
            'profile_photo_path' => null,
        ]);

        return $user;
    }


    public function completeRegisterCustomer(Request $request)
    {
        try{
            $request->validate([
                'id_number' => 'required|string|unique:customers,id_number',
                'phone' => 'required|string|unique:users,phone_number',
                'cr_number' => 'required|string|unique:customers,cr_number',
                'owner_name' => 'required|string',
                'trade_name' => 'required|string',
                'category_id' => 'required|array',
                'category_id.*' => 'integer|exists:categories,id',
                'country_id' => 'required|integer|exists:countries,id',
                'state_id' => 'required|integer|exists:states,id',
                'city_id' => 'required|integer|exists:cities,id',
                'cr_file' => 'required|file|mimes:pdf,jpg,png|max:2048',
            ]);
        }catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errNum' => 'E422',
                'msg' => 'Validation error.',
                'errors' => $e->errors(),
            ], 422);
        }
        

        //$encryptionService = new EncryptionService();
        //$idNumber = $encryptionService->decrypt($request->input('id_number'));
        //$phone = $encryptionService->decrypt($request->input('phone'));
        $idNumber = $request->input('id_number');
        $phone = $request->input('phone');

        DB::beginTransaction();

        $nafathVerification = NafathVerification::where('phone_number', $phone)
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!empty($nafathVerification)) {
            if(!empty($nafathVerification->nafath_response)){
                try {
                            $nafathData = json_decode($nafathVerification->nafath_response, true);

                            // 1️⃣ رفع ملف السجل التجاري
                            $crPath = $request->file('cr_file')->store('cr_files', 'public');
                            // 1️⃣ استخراج البيانات المطلوبة من Nafath
                            $firstName = $nafathData['first_name#en'] ?? $request->owner_name;
                            $fatherName = $nafathData['father_name#en'] ?? '';
                            $familyName = $nafathData['family_name#en'] ?? '';
                            $dateOfBirth = $nafathData['dob#g'] ?? null;
                            // 2️⃣ انشاء المستخدم في جدول users
                            $user = User::create([
                                'first_name'     => $firstName,
                                'last_name'      => $familyName,
                                'user_type'      => 'user',
                                'email'          => $phone . '@example.com',
                                'phone_number'   => $phone,
                                'business_name'  => $request->trade_name,
                                'country_id'     => $request->country_id,
                                'state_id'       => $request->state_id,
                                'city_id'        => $request->city_id,
                                'password'       => Hash::make('12345678'),
                            ]);

                            // 3️⃣ انشاء العميل في جدول customers
                            $customer = Customer::create([
                                'user_id'          => $user->id,
                                'id_owner'         => $firstName . ' ' . $fatherName . ' ' . $familyName,
                                'id_number'        => $idNumber,
                                'cr_number'        => $request->cr_number,
                                'business_category_id' => implode(',', $request->category_id),
                                'address'          => '',
                                'cr_data'          => json_encode(['file_path' => $crPath]),
                                'check_nafath'     => 1,
                                'nafath_data'      => $nafathVerification->nafath_response,
                                'date_of_birth'    => $dateOfBirth,
                                'status'           => 'pending',
                            ]);

                            DB::commit();

                        return $this->returnData(['user_id' => $user->id,
                                'customer_id' => $customer->id,], 'Registration completed successfully.');  
                        } catch (\Exception $e) {
                            DB::rollBack();
                            Log::error('Registration completion failed: ' . $e->getMessage());

                            return response()->json([
                                'status' => false,
                                'errNum' => 'E500',
                                'msg' => 'An error occurred during registration.',
                            ], 500);
                        }
            }
                return response()->json([
                'status' => false,
                'errNum' => 'E500',
                'msg' => 'The Nafath response is empty.',
            ], 500);
        }

        return response()->json([
            'status' => false,
            'errNum' => 'E500',
            'msg' => 'The Nafath verification is not completed',
        ], 500);
        
    }


    public function logout(Request $request)
    {
        // حذف التوكين الحالي فقط (Logout لهذا الجهاز فقط)
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => 'Logout successfully.',
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

    function normalizePhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone); // remove any non-numeric chars

        if (Str::startsWith($phone, '0')) {
            $phone = '966' . substr($phone, 1);
        }

        return $phone;
    }

    function denormalizePhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone); // فقط أرقام

        if (Str::startsWith($phone, '966')) {
            $phone = '0' . substr($phone, 3);
        }

        return $phone;
    }


}
