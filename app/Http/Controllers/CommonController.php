<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\AdsSlider;
use App\Models\Brand;
use App\Models\BusinessCategory;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Order;
use App\Models\Product;

use App\Models\State;
use App\Traits\ApiResponseTrait;
use Auth;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommonController extends Controller
{
    use ApiResponseTrait;
    public function getBusinessCategories(): JsonResponse
    {
        $categories = BusinessCategory::select('id', 'name')
            ->orderBy('order_level', 'asc')
            ->get();

        return $this->returnData($categories, 'Business categories retrieved successfully.');
    }

    public function getCountries(): JsonResponse
    {
        $countries = Country::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();
        
        return $this->returnData($countries, 'Countries retrieved successfully.');
    }

    public function getStates($country_id): JsonResponse
    {
        $states = State::select('id', 'country_id', 'name')
            ->orderBy('id', 'asc')
            ->get();

        return $this->returnData($states, 'States retrieved successfully.');
    }

    public function getCities($state_id): JsonResponse
    {
        $cities = City::select('id', 'state_id', 'name')
            ->where('state_id', $state_id)
            ->orderBy('id', 'asc')
            ->get();
        
        return $this->returnData($cities, 'Cities retrieved successfully.');
    }

    public function getConfigSystemApp(): JsonResponse
    {
        // Assuming you have a model for system configurations
        //$config = DB::table('system_configurations')->first();
        $config =  [ "maintenance_mode"=> false, "app_version"=> [ "ios"=> [ "version"=> "1.1.3", "mandatory"=> true, "link"=> "https://apps.apple.com/sa/app/arabianpay/id6499426744" ], "android"=> [ "version"=> "1.0.1", "mandatory"=> true, "link"=> "https://play.google.com/store/apps/details?id=com.kayanintelligence.arabianpay" ] ] ]; // Replace with actual logic to fetch system configuration
        return $this->returnData($config, 'System configuration retrieved successfully.');
    }

    public function getSocialMedia(): JsonResponse
    {
        

        // إن ما عندك بيانات في الجدول، نستخدم الافتراضي المطلوب حرفيًا
       
            $rows = collect([
                ['id' => 1, 'name' => 'facebook',  'link' => 'https://www.facebook.com/profile.php?id=61552709156007'],
                ['id' => 2, 'name' => 'tiktok',    'link' => 'https://www.tiktok.com/@arabianpay'],
                ['id' => 3, 'name' => 'x',         'link' => 'https://x.com/arabianpay'],
                ['id' => 4, 'name' => 'linkedin',  'link' => 'https://www.linkedin.com/company/arabianpay'],
                ['id' => 5, 'name' => 'instagram', 'link' => 'https://www.instagram.com/arabian.pay'],
                ['id' => 6, 'name' => 'snpachat',  'link' => 'https://www.snapchat.com/add/arabianpay'],
            ]);
        

        // نرجّع مصفوفة العناصر مباشرة (نفس الشكل المطلوب)
        return $this->returnData($rows, 'Social media links retrieved successfully.');
    }

    public function getCommonQuestion(): JsonResponse
    {
        //$questions = DB::table('common_questions')->get();
        $questions = [['title'=>'test','description'=>'test test']];
        return $this->returnData($questions, 'Common questions retrieved successfully.');
    }

    public function changeLanguage(Request $request): JsonResponse
    {
        $language = $request->input('lang');
        if (!in_array($language, ['en', 'ar'])) {
            return response()->json([
                'status' => true,
                'errNum' => 'S422',
                'msg' => 'Invalid language code.',
            ]);
        }

        // Assuming you have a way to set the language in the session or user profile
        session(['app_locale' => $language]);

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => 'Language changed successfully.',
        ]);

    }

    public function adsStatistics(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'image_id' => 'required|integer|exists:ads_slider,id',
            ]);
        } catch (\Exception $e) {
            return $this->returnError('Validation error: ' . $e->getMessage());
        }
        // Assuming you have a model for ads statistics
        $statistics = DB::table('ads_slider')
        ->where('id',$request->image_id)->first();

        return $this->returnData($statistics, 'Ads statistics retrieved successfully.');
    }
}