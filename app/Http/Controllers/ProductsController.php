<?php

namespace App\Http\Controllers;

use App\Models\AdsSlider;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Slider;
use Illuminate\Http\JsonResponse;

class ProductsController extends Controller
{

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
                'thumbnail_image' => $product->thumbnail,
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

        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => '',
            'data' => [
                'total' => $products->total(),
                'products' => $productsTransformed,
            ],
        ]);
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
        return $path ? url($path) : url('/assets/img/placeholder.jpg');
    }

    public function getProductDetails($id)
    {
        $product = Product::with([
            'brand:id,name,logo',
            'shop' => function ($q) {
                $q->select('id', 'name', 'logo', 'user_id');
            },
            //'stocks' // if you have a product_stocks table
        ])
        ->where('id', $id)
        ->where('published', 'published')
        ->firstOrFail();

        // build photos array
        $photos = collect(json_decode($product->photos ?? '[]'))->map(function ($path) {
            return [
                'variant' => '',
                'path' => url($path),
            ];
        })->toArray();

        // tags: convert tags string to array
        $tags = array_filter(array_map('trim', explode(',', $product->tags ?? '')));

        // choice options and colors: assuming variants/colors stored in your products table or other relations
        $choiceOptions = []; // Fill with your own logic if you have choice options
        $colors = [];        // Fill with your own logic if you have colors

        // stock details
        /*
        $stocks = $product->stocks ? $product->stocks->map(function ($stock) use ($product) {
            return [
                'image' => $stock->image ? url($stock->image) : null,
                'qty' => (int)$stock->qty,
                'sku' => $stock->sku,
                'variant' => $stock->variant,
                'stroked_price' => [
                    'amount' => number_format((float)$product->unit_price, 2),
                    'symbol' => 'SR',
                ],
                'main_price' => [
                    'amount' => number_format((float)$this->calculateMainPrice($product), 2),
                    'symbol' => 'SR',
                ],
            ];
        }) : [];

        */
        return response()->json([
            'status' => true,
            'errNum' => 'S200',
            'msg' => '',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'added_by' => $product->added_by,
                'seller_id' => $product->user_id,
                'shop_id' => optional($product->shop)->id ?? null,
                'shop_name' => optional($product->shop)->name ?? '',
                'shop_logo' => optional($product->shop) ? $product->shop->logo : null,
                'photos' => $photos,
                'thumbnail_image' => url($product->thumbnail),
                'tags' => $tags,
                'choice_options' => $choiceOptions,
                'colors' => $colors,
                'has_discount' => $product->discount > 0,
                'discount' => (float)$product->discount,
                'discount_type' => $product->discount_type,
                'stroked_price' => (float)$product->unit_price,
                'main_price' => (float)$this->calculateMainPrice($product),
                'currency_symbol' => 'SR',
                'current_stock' => (int)$product->current_stock,
                'unit' => $product->unit,
                'rating' => (float)$product->rating,
                'num_reviews' => 0, // Fill with actual reviews count if available
                'min_qty' => (int)$product->min_qty,
                'description' => $product->description,
                'downloads' => null, // or fill with actual download links if any
                'brand' => [
                    'id' => optional($product->brand)->id ?? null,
                    'name' => optional($product->brand)->name ?? '',
                    'logo' => optional($product->brand) ? url($product->brand->logo) : null,
                ],
                'is_wholesale' => false, // update if you have wholesale
                'wholesale' => [], // fill if you have wholesale tiers
                'est_shipping_time' => (int)($product->est_shipping_days ?? 0),
                'stock' => 0,
            ],
        ]);
    }



}