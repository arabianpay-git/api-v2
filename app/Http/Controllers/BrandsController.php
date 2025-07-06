<?php

namespace App\Http\Controllers;

use App\Models\AdsSlider;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopSetting;
use App\Models\Slider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandsController extends Controller
{
    public function getBrands(Request $request)
    {
        $query = Brand::query()
            ->select('id', 'name', 'logo');

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $brands = $query
            ->orderBy('id')
            ->paginate(20); // 20 brands per page

        $data = $brands->map(function ($brand) {
            return [
                'id' => $brand->id,
                'name' => $brand->name,
                'logo' => url($brand->logo),
            ];
        });

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => '',
            'data' => $data,
        ]);
    }
}