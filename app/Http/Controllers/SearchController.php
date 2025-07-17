<?php

namespace App\Http\Controllers;


use App\Models\Product;
use App\Models\Review;
use App\Traits\ApiResponseTrait;
use Auth;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    use ApiResponseTrait;
    public function keywords()
    {
        $keywords = DB::table('searches')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('query');

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'data' => $keywords
        ]);
    }

    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|max:255',
        ]);

        $query = $request->q;

        DB::table('searches')->updateOrInsert(
            ['query' => $query],
            ['count' => DB::raw('count + 1'), 'updated_at' => now()]
        );

        // Example: Search in products
        $results = Product::where('name', 'like', '%' . $query . '%')->get();

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'data' => $results
        ]);
    }
 }