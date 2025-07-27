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
        $config = null; // Replace with actual logic to fetch system configuration
        return $this->returnData($config, 'System configuration retrieved successfully.');
    }

    public function getSocialMedia(): JsonResponse
    {
        //$socialMedia = DB::table('social_media')->get();
        $socialMedia = null; // Replace with actual logic to fetch social media links
        return $this->returnData($socialMedia, 'Social media links retrieved successfully.');
    }

    public function getCommonQuestion(): JsonResponse
    {
        //$questions = DB::table('common_questions')->get();
        $questions = ['title'=>'test','description'=>'test test'];
        return $this->returnData($questions, 'Common questions retrieved successfully.');
    }

    public function changeLanguage(Request $request): JsonResponse
    {
        $language = $request->input('lang');
        if (!in_array($language, ['en', 'ar'])) {
            return $this->returnError('Invalid language code.');
        }

        // Assuming you have a way to set the language in the session or user profile
        session(['app_locale' => $language]);

        return $this->returnData('','Language changed successfully.');
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