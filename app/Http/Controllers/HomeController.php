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
                'thumbnail',
                'discount',
                'discount_type',
                'unit_price as main_price',
                'unit_price as stroked_price',
                'rating',
                'current_stock'
            ])
            ->with(['brand:id,name'])
            ->where('published', 'published')
            ->orderByDesc('number_of_sales')
            ->limit(15)
            ->get()
            ->map(function ($product) {
                $mainPrice = (float)$product->main_price;
                $discount = (float)$product->discount;
                $discountedPrice = $mainPrice;

                if (strtolower($product->discount_type) === 'percent') {
                    $discountedPrice = $mainPrice - ($mainPrice * $discount / 100);
                } elseif (strtolower($product->discount_type) === 'amount') {
                    $discountedPrice = $mainPrice - $discount;
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'brand' => $product->brand->name ?? 'عام',
                    'thumbnail_image' => $product->thumbnail??'https://api.arabianpay.net/public/placeholder.jpg',
                    'has_discount' => $discount > 0,
                    'discount' => $discount,
                    'discount_type' => $product->discount_type,
                    'stroked_price' => $mainPrice,
                    'main_price' => max($discountedPrice, 0), // prevent negative pricing
                    'rating' => (float)$product->rating,
                    'num_reviews' => 0, // can replace if using reviews
                    'is_wholesale' => false,
                    'currency_symbol' => 'SR',
                    'in_stock' => $product->current_stock > 0,
                ];
            });
    }



    protected function getFeaturedProducts()
    {
        return Product::select([
                'id',
                'name',
                'thumbnail',
                'discount',
                'discount_type',
                'unit_price as main_price',
                'unit_price as stroked_price',
                'rating',
                'current_stock'
            ])
            ->with(['brand:id,name'])
            ->where('published', 'published')
            ->where('featured', 1)
            ->orderByDesc('number_of_sales')
            ->limit(15)
            ->get()
            ->map(function ($product) {
                $mainPrice = (float)$product->main_price;
                $discount = (float)$product->discount;
                $discountedPrice = $mainPrice;

                if (strtolower($product->discount_type) === 'percent') {
                    $discountedPrice = $mainPrice - ($mainPrice * $discount / 100);
                } elseif (strtolower($product->discount_type) === 'amount') {
                    $discountedPrice = $mainPrice - $discount;
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'brand' => $product->brand->name ?? 'عام',
                    'thumbnail_image' => $product->thumbnail??'https://api.arabianpay.net/public/placeholder.jpg',
                    'has_discount' => $discount > 0,
                    'discount' => $discount,
                    'discount_type' => $product->discount_type,
                    'stroked_price' => $mainPrice,
                    'main_price' => max($discountedPrice, 0), // prevent negative pricing
                    'rating' => (float)$product->rating,
                    'num_reviews' => 0, // can replace if using reviews
                    'is_wholesale' => false,
                    'currency_symbol' => 'SR',
                    'in_stock' => $product->current_stock > 0,
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
                'logo' => $shop->logo??'https://api.arabianpay.net/public/placeholder.jpg',
                'cover' => 'https://api.arabianpay.net/public/placeholder.jpg', // Placeholder cover image
                'rating' => 0, // Since your table has no rating
            ];
        });
    }


    protected function getSliders()
    {

        return AdsSlider::select('image', 'id')->get()
            ->map(function ($slider) {
                return [
                    'image' => $slider->image??'https://api.arabianpay.net/public/placeholder.jpg', // get URL by ID
                    'image_id' => (string) $slider->id,
                    'target' => '', // No target data in table
                ];
            });
    }
    

    protected function getBanner1()
    {
        return AdsSlider::select('image', 'id')
            ->get()
            ->map(function ($slider) {
                return [
                    'image' => $slider->image??'https://api.arabianpay.net/public/placeholder.jpg',  // convert image ID to URL
                    'image_id' => (string) $slider->id,
                    'target' => '', // no target info in current schema
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

    protected function resolveTargetType($type)
    {
        return match(class_basename($type)) {
            'Shop' => 'shop',
            'Brand' => 'brand',
            'Category' => 'category',
            default => 'unknown',
        };
    }
}
