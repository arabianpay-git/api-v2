<?php

namespace App\Http\Controllers;

use App\Models\AdsSlider;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopSetting;
use App\Models\Slider;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuppliersController extends Controller
{
    use ApiResponseTrait;
   public function getSuppliers()
    {
        $name = $_REQUEST['name'] ?? '';
        $shops = ShopSetting::orderBy('id', 'desc');
        if ($name) {
            $shops = $shops->where('name', 'like', '%' . $name . '%');
        }

        $shops = $shops->where('name','!=','')
        ->where('name','!=',null)->orderBy('id','DESC')->get();

        $data = $shops->map(function ($shop) {
            return [
                'id' => $shop->id,
                'slug' => str()->slug($shop->name) . '-' . $shop->id,
                'user_id' => $shop->user_id,
                'name' => $shop->name ?? 'Unknown',
                'logo' => $shop->logo?'https://partners.arabianpay.net'.$shop->logo:'https://api.arabianpay.net/public/placeholder.jpg',
                'cover' => asset('assets/img/placeholder.jpg'),
                'rating' => 0, // You can replace with actual rating field if available
            ];
        });

        return $this->returnData($data, 'Suppliers retrieved successfully.');
    }

    public function getSupplierDetails($id)
    {
        $shop = ShopSetting::findOrFail($id);

        $user = $shop->user; // Assuming ShopSetting has relation to User

        $data = [
            'id' => $shop->id,
            'name' => $shop->name ?? 'Unknown',
            'logo' => $shop->logo?'https://partners.arabianpay.net'.$shop->logo:'https://api.arabianpay.net/public/placeholder.jpg',
            'return_policy' => '', // Replace with actual data if exists
            'exchange_policy' => '', // Replace with actual data if exists
            'cancel_policy' => '', // Replace with actual data if exists
            'top_banner' => asset('assets/img/placeholder.jpg'), // Replace if shop has a real banner
            'address' => $shop->address ?? '',
            'phone' => $shop->phone_number ?? '',
            'email' => $user ? $user->email : '',
            'rating' => 0, // Replace if you have ratings
            'num_reviews' => 0, // Replace if you have reviews
            'is_followed' => false, // Replace if you have follow logic
            'featured_products' => [], // Replace if you want actual featured products
        ];

        return $this->returnData($data, 'Supplier details retrieved successfully.');
        
    }

    public function getSupplierProducts(Request $request, $id)
    {
        $shop = ShopSetting::findOrFail($id);

        $products = Product::with('brand')
            ->where('user_id', $shop->user_id)
            ->latest()
            ->paginate(20);

        $data = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'brand' => $product->brand ? $product->brand->name : '',
                'thumbnail_image' => $product->thumbnail ? 'https://partners.arabianpay.net'.$product->thumbnail : 'https://api.arabianpay.net/public/placeholder.jpg',
                'has_discount' => (bool) ($product->discount > 0),
                'discount' => (float) $product->discount,
                'discount_type' => $product->discount_type ?? 'amount',
                'stroked_price' => (float) $product->stroked_price,
                'main_price' => (float) $product->main_price,
                'rating' => (float) ($product->rating ?? 0),
                'num_reviews' => (int) ($product->num_reviews ?? 0),
                'is_wholesale' => (bool) ($product->is_wholesale ?? false),
                'currency_symbol' => 'SR',
                'in_stock' => (bool) ($product->in_stock ?? true),
            ];
        });

        return $this->returnData($data, 'Supplier products retrieved successfully.');
    }

}