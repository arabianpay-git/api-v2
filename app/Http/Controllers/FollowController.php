<?php

namespace App\Http\Controllers;


use App\Models\Product;
use App\Models\Review;
use App\Traits\ApiResponseTrait;
use Auth;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    use ApiResponseTrait;
    public function get(Request $request)
    {
        $follows = DB::table('follow_sellers')
            ->where('user_id', $request->user()->id)
            ->pluck('shop_id');

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'data' => $follows
        ]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|exists:shop_settings,id',
        ]);

        $exists = DB::table('follow_sellers')
            ->where('user_id', $request->user()->id)
            ->where('shop_id', $request->shop_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'errNum' => 'E422',
                'msg' => trans('api.shop_already_followed')
            ]);
        }

        DB::table('follow_sellers')->insert([
            'user_id' => $request->user()->id,
            'shop_id' => $request->shop_id
        ]);

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => trans('api.shop_followed_successfully')
        ]);
    }

    public function remove(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|exists:shop_settings,id',
        ]);

        DB::table('follow_sellers')
            ->where('user_id', $request->user()->id)
            ->where('shop_id', $request->shop_id)
            ->delete();

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => trans('api.shop_unfollowed_successfully')
        ]);
    }



}