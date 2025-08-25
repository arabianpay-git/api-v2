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
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
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
        ->with(['brand:id,name'])
        ->where('published', 'published')
        ->where('approved', 'approved')
        ->whereNotNull('name')
        ->whereNotNull('thumbnail');

        // ✅ فلتر حسب الفئة (category_ids[])
        if ($request->has('category_ids') && is_array($request->category_ids)) {
            $productsQuery->whereIn('category_id', $request->category_ids);
        }

        // ✅ فلتر حسب الاسم (name)
        if ($request->filled('name')) {
            $productsQuery->where('name', 'LIKE', '%' . $request->name . '%');
        }

        // ✅ ترتيب حسب (sort_key)
        switch ($request->input('sort_key')) {
            case 'price_asc':
                $productsQuery->orderBy('unit_price', 'asc');
                break;
            case 'price_desc':
                $productsQuery->orderBy('unit_price', 'desc');
                break;
            case 'newest':
                $productsQuery->orderBy('id', 'desc');
                break;
            case 'rating':
                $productsQuery->orderBy('rating', 'desc');
                break;
            default:
                $productsQuery->orderBy('id', 'desc');
                break;
        }

        // ✅ تنفيذ الاستعلام مع pagination
        $products = $productsQuery->paginate(20);

        // ✅ تحويل النتائج
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
                'num_reviews' => 0,
                'is_wholesale' => false,
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

    public function getProductRelated($id)
    {
        $product = Product::findOrFail($id);

        $relatedProducts = Product::where('id', '!=', $id)
            ->where(function ($q) use ($product) {
                $q->where('brand_id', $product->brand_id)
                ->orWhere('category_id', $product->category_id);
            })
            ->where('published', 'published')
            ->whereNotNull('name')
            ->whereNotNull('thumbnail')
            ->take(10)
            ->get();

        $results = $relatedProducts->map(function ($item) {
            $hasDiscount = $item->discount > 0;
            $discountAmount = $item->discount ?? 0;
            $price = (float) $item->unit_price;

            if ($item->discount_type === 'percent') {
                $discountedPrice = $price - ($price * $discountAmount / 100);
            } else {
                $discountedPrice = $price - $discountAmount;
            }

            return [
                'id'              => $item->id,
                'name'            => $item->name,
                'brand'           => optional($item->brand)->name ?? 'Generic',
                'thumbnail_image' => $this->fullImageUrl($item->thumbnail),
                'has_discount'    => $hasDiscount,
                'discount'        => (float) $discountAmount,
                'discount_type'   => $item->discount_type ?? 'amount',
                'stroked_price'   => round($price, 2),
                'main_price'      => round($discountedPrice, 2),
                'rating'          => (float) ($item->rating ?? 0),
                'num_reviews'     => 0,
                'is_wholesale'    => false,
                'currency_symbol' => 'SR',
                'in_stock'        => $item->current_stock > 0,
            ];
        });

        return $this->returnData($results,'get related products successfully');
    }

    public function getProductReviews(Request $request){
        $this->returnData('','success');
    }


    public function getProductWishlists(Request $request){
        $this->returnData([],'success');
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

    public function checkVariation(){
        return $this->returnData(["colors"=>[],"choice_options"=>[]],'success');
    }
    public function getProductDetails($id)
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

        return $this->returnData([
            "id" => $product->id,
            "name" => $product->name,
            "slug" => $product->slug,
            "added_by" => $product->added_by,
            "seller_id" => $product->user_id,
            "shop_id" => $shop->id ?? 0,
            "shop_name" => $shop->name ?? '',
            "shop_logo" => $this->fullImageUrl($shop->logo??''),
            "photos" => collect(
                json_decode($product->photos, true) ?: [$product->thumbnail]
            )->map(fn($photo) => [
                "variant" => "",
                "path" => $this->fullImageUrl($photo)
            ]),
            "thumbnail_image" => $this->fullImageUrl($product->thumbnail),
            "tags" => [],
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
        ],'Product success');
    }

    public function getProductFilters()
    {
        try{
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
        }catch(Exception $ex){
            return $this->returnData([]);
        }
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