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
}