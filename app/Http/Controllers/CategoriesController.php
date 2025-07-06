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

class CategoriesController extends Controller
{
    public function getCategories()
    {
        $categories = Category::where('parent_id', null)
            ->with(['children'])
            ->orderBy('id')
            ->get();

        $data = $categories->map(function ($category) {
            return $this->formatCategory($category);
        });

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => '',
            'data' => $data,
        ]);
    }

    private function formatCategory(Category $category)
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'image' => url($category->banner ?? 'assets/img/placeholder.jpg'),
            'parent_id' => $category->parent_id ?? 0,
            'children' => $category->children
                ? $category->children->map(function ($child) {
                    return $this->formatCategory($child);
                })->toArray()
                : [],
        ];
    }
}