<?php

namespace App\Http\Controllers;

use App\Models\AdsSlider;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopSetting;
use App\Models\Slider;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    use ApiResponseTrait;
   
    public function getTermsConditions(): JsonResponse
    {
        // Assuming you have a TermsConditions model or similar to fetch the content
        $terms = 'Terms and conditions content goes here.'; // Replace with actual data retrieval logic

        return $this->returnData(['title'=>'شروط وأحكام المستخدم','content' => $terms], 'Terms and conditions retrieved successfully.');
    }

    public function getReplacementPolicy(): JsonResponse
    {
        // Assuming you have a ReplacementPolicy model or similar to fetch the content
        $policy = 'Replacement policy content goes here.'; // Replace with actual data retrieval logic

        return $this->returnData(['title'=>'replacement policy','content' => $policy], 'Replacement policy retrieved successfully.');
    }

}