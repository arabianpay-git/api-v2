<?php

namespace App\Http\Controllers;

use App\Models\AdsSlider;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopSetting;
use App\Models\Slider;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Str;

class CategoriesController extends Controller
{
    use ApiResponseTrait;
    public function getCategories()
    {
        $categories = Category::where('parent_id', null)
            ->with(['children'])
            ->orderBy('id')
            ->get();

        $data = $categories->map(function ($category) {
            return $this->formatCategory($category);
        });

        return $this->returnData($data, 'Categories retrieved successfully.');
    }

    private function formatCategory(Category $category)
    {
        if(!empty($category->icon)){
            if (Str::startsWith($category->icon, '/storage')) {
                $img = 'https://core.arabianpay.net'.$category->icon;
            }else{
                $img = 'https://core.arabianpay.net'.$category->icon;
            }
        }else{
            $img = 'https://api.arabianpay.net/public/placeholder.jpg';
        }
        
        return [
            'id' => $category->id,
            'name' => $category->name,
            'image' => $img,
            'parent_id' => $category->parent_id ?? 0,
            'children' => $category->children
                ? $category->children->map(function ($child) {
                    return $this->formatCategory($child);
                })->toArray()
                : [],
        ];
    }
}