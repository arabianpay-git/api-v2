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

use App\Models\Review;
use App\Traits\ApiResponseTrait;
use Auth;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    use ApiResponseTrait;
   
    public function get(Request $request)
    {
        $wishlist = \App\Models\Wishlist::with('product')
            ->where('user_id', $request->user()->id)
            ->get();

        return $this->returnData($wishlist);
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $exists = \App\Models\Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $request->product_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'errNum' => 'E409',
                'msg' => trans('api.product_already_in_wishlist')
            ], 409);
        }

        \App\Models\Wishlist::create([
            'user_id' => $request->user()->id,
            'product_id' => $request->product_id,
        ]);

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => trans('api.product_added_to_wishlist')
        ]);
    }

    public function remove(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        \App\Models\Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $request->product_id)
            ->delete();

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => trans('api.product_removed_from_wishlist')
        ]);
    }

 }