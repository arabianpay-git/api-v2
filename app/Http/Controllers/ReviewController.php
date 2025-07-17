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

class ReviewController extends Controller
{
    use ApiResponseTrait;
    public function getProductReviews(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $reviews = Review::where('product_id', $request->product_id)
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'rating', 'comment', 'photos', 'created_at']);

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'data' => $reviews
        ]);
    }

    public function addProductReview(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'order_id' => 'required|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string',
            'photos' => 'nullable|array',
        ]);

        Review::create([
            'user_id' => $request->user()->id,
            'product_id' => $request->product_id,
            'order_id' => $request->order_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'photos' => json_encode($request->photos),
            'status' => 1,
            'viewed' => 0,
        ]);

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => 'Review added successfully.'
        ]);
    }

    public function getSuppliersRating(Request $request)
    {
        $ratings = DB::table('reviews_store')
            ->select('store_id', DB::raw('AVG(rating) as average_rating'))
            ->groupBy('store_id')
            ->get();

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'data' => $ratings
        ]);
    }
 }