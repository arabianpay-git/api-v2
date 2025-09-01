<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\AdsSlider;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;

use App\Traits\ApiResponseTrait;
use Auth;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    use ApiResponseTrait;
    public function list(Request $request): JsonResponse
    {
        $coupons = Coupon::where('user_id', $request->user()->id)
        ->orWhereNull('user_id') // or global coupons
        ->get();
        return $this->returnData($coupons, trans('api.coupons_listed_successfully'));
    }

    public function apply(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string'
        ]);

        $coupon = Coupon::where('code', $request->coupon_code)->first();

        if (!$coupon) {
            return response()->json([
                'status' => false,
                'errNum' => 'E422',
                'msg' => trans('api.invalid_coupon'),
            ]);
        }

        // You can store applied coupon in session, db, etc.
        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => trans('api.coupon_applied_successfully'),
            'discount' => $coupon->discount ?? 0,
        ]);
    }

    public function remove(Request $request)
    {
        // Remove applied coupon from session/db if needed
        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => trans('api.coupon_removed_successfully')
        ]);
    }

    public function products(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string'
        ]);

        $coupon = Coupon::with('products')->where('code', $request->coupon_code)->first();

        if (!$coupon) {
            return response()->json([
                'status' => false,
                'errNum' => 'E422',
                'msg' => trans('api.invalid_coupon'),
            ]);
        }

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'data' => $coupon->products
        ]);
    }
}