<?php

namespace App\Http\Controllers;

use App\Helpers\EncryptionService;
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
   use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\ShopSetting;

public function getSuppliers(Request $request)
{
    $q        = trim((string) $request->input('name', ''));
    $qLower   = mb_strtolower($q);
    $page     = max(1, (int) $request->input('page', 1));
    $perPage  = min(50, max(5, (int) $request->input('per_page', 20)));

    $service  = new EncryptionService();

    // هنجهز كولكشن النتائج
    $matches = collect();

    // بنقلل الأعمدة المسحوبة من DB
    $baseQuery = ShopSetting::query()
        ->select(['id','user_id','name','logo'])
        ->whereNotNull('name')
        ->orderByDesc('id');

    if ($qLower !== '') {
        // نفلتر يدوياً بعد فك التشفير باستخدام chunkById لتقليل الذاكرة
        $baseQuery->chunkById(500, function ($chunk) use ($service, $qLower, &$matches) {
            foreach ($chunk as $row) {
                try {
                    $plain = $service->decrypt($row->name);
                    if ($plain === null || $plain === '') continue;

                    // بحث جزئي بدون حساسية لحالة الأحرف
                    if (mb_stripos($plain, $qLower) !== false) {
                        $matches->push([
                            'id'      => $row->id,
                            'slug'    => Str::slug($plain) . '-' . $row->id,
                            'user_id' => $row->user_id,
                            'name'    => $plain,
                            'logo'    => $row->logo
                                ? ('https://partners.arabianpay.net' . $row->logo)
                                : 'https://api.arabianpay.net/public/placeholder.jpg',
                            'cover'   => asset('assets/img/placeholder.jpg'),
                            'rating'  => 0,
                        ]);
                    }
                } catch (\Throwable $e) {
                    // تجاهل السجلات التي تفشل في فك التشفير
                    continue;
                }
            }
        });

        // ترقيم النتائج في الذاكرة
        $total   = $matches->count();
        $results = $matches->forPage($page, $perPage)->values();

        return $this->returnData([
            'data'       => $results,
            'page'       => $page,
            'per_page'   => $perPage,
            'total'      => $total,
            'total_pages'=> (int) ceil($total / $perPage),
        ], 'Suppliers retrieved successfully.');
    }

    // بدون كلمة بحث: رجّع أحدث السجلات (مع فك تشفير الاسم قبل الإرجاع)
    $rows = $baseQuery->paginate($perPage, ['*'], 'page', $page);

    $data = collect($rows->items())->map(function ($row) use ($service) {
        $plain = null;
        try { $plain = $service->decrypt($row->name); } catch (\Throwable $e) {}
        $plain = $plain ?: 'Unknown';

        return [
            'id'      => $row->id,
            'slug'    => Str::slug($plain) . '-' . $row->id,
            'user_id' => $row->user_id,
            'name'    => $plain,
            'logo'    => $row->logo
                ? ('https://partners.arabianpay.net' . $row->logo)
                : 'https://api.arabianpay.net/public/placeholder.jpg',
            'cover'   => asset('assets/img/placeholder.jpg'),
            'rating'  => 0,
        ];
    })->values();

    return $this->returnData([
        'data'        => $data,
        'page'        => $rows->currentPage(),
        'per_page'    => $rows->perPage(),
        'total'       => $rows->total(),
        'total_pages' => $rows->lastPage(),
    ], 'Suppliers retrieved successfully.');
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