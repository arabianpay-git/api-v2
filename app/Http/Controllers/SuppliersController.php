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
  

    public function getSuppliers(Request $request)
    {
        $q = trim((string) $request->input('name', ''));
        $qLower = mb_strtolower($q, 'UTF-8');

        // لا تبحث لو ما في كلمة
        if ($qLower === '') {
            $rows = ShopSetting::query()
                ->select(['id','user_id','name','logo'])
                ->whereNotNull('name')
                ->orderByDesc('id')
                ->limit(50)
                ->get();

            $service = new EncryptionService();

            $data = $rows->map(function ($row) use ($service) {
                $plain = self::safeDecrypt($service, $row->name);
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

            return $this->returnData($data, 'Suppliers retrieved successfully.');
        }

        // بحث جزئي بعد فك التشفير
        $service = new EncryptionService();

        // اسحب فقط الأعمدة اللازمة ثم فك تشفير + فلترة
        $rows = ShopSetting::query()
            ->select(['id','user_id','name','logo'])
            ->whereNotNull('name')
            ->orderByDesc('id')
            ->get();

        // فك التشفير ثم فلترة
        $filtered = $rows->map(function ($row) use ($service) {
                $row->plain_name = self::safeDecrypt($service, $row->name);
                return $row;
            })
            ->filter(function ($row) use ($qLower) {
                if (!$row->plain_name) return false;
                // بديل متوافق مع PHP7: بحث جزئي بدون حساسية لحالة الأحرف
                return mb_stripos($row->plain_name, $qLower, 0, 'UTF-8') !== false;
            })
            ->map(function ($row) {
                $plain = $row->plain_name ?: 'Unknown';
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
            })
            ->values();

        return $this->returnData($filtered, 'Suppliers retrieved successfully.');
    }

    /**
     * دالة مساعدة تفك التشفير بأمان وتتعامل مع الاستثناءات
     */
    private static function safeDecrypt($service, $cipher)
    {
        try {
            $plain = $service->decrypt($cipher);

            // لو رجعت JSON (أحياناً الأنظمة تحفظ الاسم كـ {"ar":"..","en":".."})
            if (is_string($plain) && strlen($plain) && $plain[0] === '{') {
                $j = json_decode($plain, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    // اختَر الحقل المناسب لك
                    $plain = $j['ar'] ?? $j['en'] ?? implode(' ', $j);
                }
            }

            // تأكّد أنه نص
            if (!is_string($plain)) return null;

            return $plain;
        } catch (\Throwable $e) {
            // جرب Laravel Crypt إن كنتم خلطتم بين طريقتين للتشفير
            try {
                $plain = \Illuminate\Support\Facades\Crypt::decryptString($cipher);
                return is_string($plain) ? $plain : null;
            } catch (\Throwable $e2) {
                return null;
            }
        }
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