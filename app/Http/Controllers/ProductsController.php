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

class ProductsController extends Controller
{
    use ApiResponseTrait;

    public function index()
    {
        $productsQuery = Product::select([
            'id',
            'name',
            'brand_id',
            'thumbnail',
            'discount',
            'discount_type',
            'unit_price as main_price',
            'unit_price as stroked_price',
            'rating',
            'current_stock'
        ])
        ->with(['brand:id,name']) // eager-load brand name
        ->where('published', 'published')
        ->orderByDesc('id'); // or any order you need

        // If you want pagination, use paginate, e.g. 20 per page:
        $products = $productsQuery->paginate(20);

        // Build the products array to match your exact response format:
        $productsTransformed = $products->getCollection()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'brand' => optional($product->brand)->name ?? 'Generic',
                'thumbnail_image' => $this->fullImageUrl($product->thumbnail),
                'has_discount' => $product->discount > 0,
                'discount' => (float)$product->discount,
                'discount_type' => $product->discount_type,
                'stroked_price' => (float)$product->stroked_price,
                'main_price' => (float)$this->calculateMainPrice($product),
                'rating' => (float)$product->rating,
                'num_reviews' => 0, // add reviews count if you have reviews table
                'is_wholesale' => false, // update if you have wholesale logic
                'currency_symbol' => 'SR',
                'in_stock' => (bool)($product->current_stock > 0),
            ];
        });
        $data = [
                'total' => $products->total(),
                'products' => $productsTransformed,
        ];
         
        return $this->returnData($data);
    }

    /**
     * Calculate the discounted main price.
     */
    protected function calculateMainPrice($product)
    {
        $price = $product->stroked_price;

        if ($product->discount > 0) {
            if (strtolower($product->discount_type) === 'percent') {
                $discountAmount = $price * ($product->discount / 100);
                return max($price - $discountAmount, 0);
            } elseif (strtolower($product->discount_type) === 'amount') {
                return max($price - $product->discount, 0);
            }
        }

        return $price;
    }

    /**
     * Return the full URL for thumbnails.
     */
    protected function fullImageUrl($path)
    {
        return $path ? 'https://partners.arabianpay.net'.$path : 'https://api.arabianpay.net/public/placeholder.jpg';
    }

    public function productDetails($id)
    {
        $product = Product::findOrFail($id);
        $shop = ShopSetting::where('user_id', $product->user_id)->first();
        $brand = Brand::find($product->brand_id);

        $unitPrice = $product->unit_price;
        $discount = $product->discount ?? 0;
        $hasDiscount = $discount > 0;
        $mainPrice = $hasDiscount
            ? ($product->discount_type === 'percent'
                ? $unitPrice - ($unitPrice * $discount / 100)
                : $unitPrice - $discount)
            : $unitPrice;

        return response()->json([
            "id" => $product->id,
            "name" => $product->name,
            "slug" => $product->slug,
            "added_by" => $product->added_by,
            "seller_id" => $product->user_id,
            "shop_id" => $shop->id ?? 0,
            "shop_name" => $shop->name ?? '',
            "shop_logo" => $this->fullImageUrl($shop->logo),
            "photos" => collect(json_decode($product->photos, true))->map(fn($photo) => [
                "variant" => "",
                "path" => $this->fullImageUrl($photo)
            ]),
            "thumbnail_image" => $this->fullImageUrl($product->thumbnail),
            "tags" => json_decode($product->tags, true) ?? [],
            "choice_options" => [], // إذا كان عندك خيارات، املأها
            "colors" => [], // إذا كان عندك ألوان، املأها
            "has_discount" => $hasDiscount,
            "discount" => (float) $discount,
            "discount_type" => $product->discount_type,
            "stroked_price" => round($unitPrice, 2),
            "main_price" => round($mainPrice, 2),
            "currency_symbol" => "SR",
            "current_stock" => $product->current_stock,
            "unit" => $product->unit,
            "rating" => (float) $product->rating ?? 0,
            "num_reviews" => 0, // إذا عندك جدول مراجعات اربطه هنا
            "min_qty" => $product->min_qty ?? 1,
            "description" => $product->description,
            "downloads" => null,
            "brand" => $brand ? [
                "id" => $brand->id,
                "name" => $brand->name,
                "logo" => $this->fullImageUrl($brand->logo)
            ] : null,
            "is_wholesale" => false,
            "wholesale" => [],
            "est_shipping_time" => (int) ($product->est_shipping_days ?? 0),
            "stock" => [[
                "image" => null,
                "qty" => $product->current_stock,
                "sku" => $product->sku,
                "variant" => "",
                "stroked_price" => [
                    "amount" => number_format($unitPrice, 2),
                    "symbol" => "SR"
                ],
                "main_price" => [
                    "amount" => number_format($mainPrice, 2),
                    "symbol" => "SR"
                ]
            ]]
        ]);
    }

    public function getProductFilters()
    {
        // 1) Fetch brands: all published brands used in products
        $brands = Brand::select('id', 'name')
            ->whereHas('products') // if you only want brands with products
            ->orderBy('name')
            ->get();

        // 2) Fetch categories recursively with children
        $categories = Category::with(['childrenRecursive'])
            ->where('parent_id', 0)
            ->select('id', 'name', 'banner as image', 'parent_id')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'image' => $category->image ?'https://core.arabianpay.net'.$category->image:'https://api.arabianpay.net/public/placeholder.jpg',
                    'parent_id' => $category->parent_id,
                    'children' => $this->mapChildren($category->childrenRecursive),
                ];
            });

        // 3) Fetch stores from shops
        $stores = ShopSetting::select('id', 'user_id', 'name', 'logo')
            ->limit(50) // optional limit
            ->get()
            ->map(function ($shop) {
                return [
                    'id' => $shop->id,
                    'slug' => \Str::slug($shop->name) . '-' . $shop->id,
                    'user_id' => $shop->user_id,
                    'name' => $shop->name,
                    'logo' => url($shop->logo),
                    'cover' => $this->fullImageUrl($shop->banner),
                    'rating' => 0, // static or pull from review system
                ];
            });

        $data = [
            'brands' => $brands,
            'categories' => $categories,
            'stores' => $stores,
        ];

        return $this->returnData($data);
    }

    /**
     * Helper to map nested category children recursively
     */
    private function mapChildren($children)
    {
        return $children->map(function ($child) {
            return [
                'id' => $child->id,
                'name' => $child->name,
                'image' => $this->fullImageUrl($child->image),
                'parent_id' => $child->parent_id,
                'children' => $this->mapChildren($child->childrenRecursive),
            ];
        });
    }

}