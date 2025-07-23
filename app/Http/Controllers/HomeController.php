<?php

namespace App\Http\Controllers;

use App\Helpers\EncryptionService;
use App\Models\AdsSlider;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Slider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(): JsonResponse
    {
        $data = [
            'topBrand' => $this->getTopBrands(),
            'featuredCategories' => $this->getFeaturedCategories(),
            'bestSellerProducts' => $this->getBestSellerProducts(),
            'featuredProducts' => $this->getFeaturedProducts(),
            'topShops' => $this->getTopShops(),
            'sliders' => $this->getSliders(),
            'banner0' => [],
            'banner1' => $this->getBanner1(),
            'banner2' => [],
            'products_order' => []
        ];

        $encryptionService = new EncryptionService();
        $encryptedData = $encryptionService->encrypt($data);

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => '',
            'data' => $encryptedData,
        ]);
    }

    protected function getTopBrands()
    {
        return Brand::select('id', 'logo')
        ->with(['translations' => function ($query) {
            $query->where('locale', 'ar'); // or the current locale
        }])
        ->limit(25)
        ->get()
        ->map(function ($brand) {
            return [
                'id' => $brand->id,
                'name' => $brand->translations[0]->name ?? '',
                'logo' => $brand->logo,
            ];
        });
    }

    protected function getFeaturedCategories()
    {
        return Category::with(['children' => function ($query) {
            $query->select('id', 'name', 'icon as image', 'parent_id');
        }])
        ->where('parent_id', null)
        ->select('id', 'name', 'icon as image', 'parent_id')
        ->get()
        ->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'image' => $category->image??'https://api.arabianpay.net/public/placeholder.jpg',
                'parent_id' => $category->parent_id,
                'children' => $category->children->map(function ($child) {
                    return [
                        'id' => $child->id,
                        'name' => $child->name,
                        'image' => $child->image??'https://api.arabianpay.net/public/placeholder.jpg',
                        'parent_id' => $child->parent_id,
                        'children' => [], // Optional: nest deeper if needed
                    ];
                })->toArray(),
            ];
        });
    }


    protected function getBestSellerProducts()
    {
        return Product::select([
            'id',
            'name',
            'thumbnail as thumbnail_image',
            'discount',
            'discount_type',
            'unit_price as main_price',
            'unit_price as stroked_price', // since you don't have separate prices, set both
            'rating',
            'current_stock as in_stock'
        ])
        ->with(['brand:id,name']) // eager-load brand name
        ->orderByDesc('number_of_sales') // best sellers = most sales
        ->where('published', 1) // only published products
        ->limit(15)
        ->get()
        ->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'brand' => optional($product->brand)->name ?? '',
                'thumbnail_image' => url($product->thumbnail), // convert to full URL
                'has_discount' => $product->discount > 0,
                'discount' => (float)$product->discount,
                'discount_type' => $product->discount_type,
                'stroked_price' => (float)$product->stroked_price,
                'main_price' => (float)$product->main_price - (strtolower($product->discount_type) == 'percent'
                    ? ($product->main_price * $product->discount / 100)
                    : $product->discount),
                'rating' => (float)$product->rating,
                'num_reviews' => 0, // you can calculate reviews count if you have reviews table
                'is_wholesale' => false, // hardcoded; update if you track wholesale
                'currency_symbol' => 'SR', // hardcoded; update if dynamic
                'in_stock' => (bool)($product->in_stock > 0),
            ];
        });
    }


    protected function getFeaturedProducts()
    {
        return Product::select([
            'id',
            'name',
            'thumbnail as thumbnail_image',
            'discount',
            'discount_type',
            'unit_price as main_price',
            'unit_price as stroked_price', // since there's no separate price fields
            'rating',
            'current_stock as in_stock'
        ])
        ->with(['brand:id,name']) // eager-load brand relationship
        ->where('featured', 1)    // use your existing `featured` column
        ->where('published', 1)   // only show published products
        ->limit(10)
        ->get()
        ->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'brand' => optional($product->brand)->name ?? '',
                'thumbnail_image' => url($product->thumbnail),
                'has_discount' => $product->discount > 0,
                'discount' => (float)$product->discount,
                'discount_type' => $product->discount_type,
                'stroked_price' => (float)$product->stroked_price,
                'main_price' => (float)$product->stroked_price - (strtolower($product->discount_type) == 'percent'
                    ? ($product->stroked_price * $product->discount / 100)
                    : $product->discount),
                'rating' => (float)$product->rating,
                'num_reviews' => 0, // update this if you track reviews
                'is_wholesale' => false, // update if you have wholesale logic
                'currency_symbol' => 'SR', // hardcoded currency
                'in_stock' => (bool)($product->in_stock > 0),
            ];
        });
    }


    protected function getTopShops()
    {
        return \App\Models\ShopSetting::select([
            'id',
            'user_id',
            'name',
            'logo',
        ])
        ->limit(20) // No rating field, so limit only
        ->get()
        ->map(function ($shop) {
            return [
                'id' => $shop->id,
                'slug' => \Str::slug($shop->name) . '-' . $shop->id, // Dynamic slug
                'user_id' => $shop->user_id,
                'name' => $shop->name,
                'logo' => url($shop->logo),
                'cover' => url('/assets/img/placeholder.jpg'), // Placeholder cover image
                'rating' => 0, // Since your table has no rating
            ];
        });
    }


    protected function getSliders()
    {
        return AdsSlider::select('image', 'id')->get()
        ->map(function ($slider) {
            return [
                'image' => $slider->image,
                'image_id' => $slider->id, // Assuming 'id' is the unique identifier
                'target' => '',
            ];
        });
    }

    protected function getBanner1()
    {
        return AdsSlider::select('image', 'id')->get()
        ->map(function ($slider) {
            return [
                'image' => $slider->image,
                'image_id' => $slider->id, // Assuming 'id' is the unique identifier
                'target' => '',
            ];
        });
    }

    public function process(Request $request)
    {
        $request->validate([
            'text' => 'required',
            'operation' => 'required|in:encrypt,decrypt',
        ]);

        $encryptionService = new EncryptionService();
        $result = null;

        if ($request->operation === 'encrypt') {
            $result = $encryptionService->encrypt($request->text);
        } else {
            $result = $encryptionService->decrypt($request->text);
        }

        return($result); // Debugging output, remove in production

        return view('encryption', [
            'input' => $request->text,
            'operation' => $request->operation,
            'result' => $result,
        ]);
    }
}
