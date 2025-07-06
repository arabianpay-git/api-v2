<?php

use App\Models\Approval;
use App\Models\CustomerCreditLimit;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

// Get Settings
if (!function_exists('get_setting')) {
    function get_setting($label, $default = null)
    {
        $setting = Setting::where('label', $label)->first();
        return $setting ? $setting->value : $default;
    }
}

// Get Tax setting
if (!function_exists('get_tax')) {
    function get_tax($default = 0)
    {
        return get_setting('tax', $default);
    }
}

// Get Commission Tax setting
if (!function_exists('get_commission_tax')) {
    function get_commission_tax($default = 0)
    {
        return get_setting('commission_tax', $default);
    }
}

if (!function_exists('get_credit_limit')) {
    function get_credit_limit($user_id, $default = 0.00)
    {
        $creditLimit = CustomerCreditLimit::where('user_id', $user_id)->latest()->first();
        return $creditLimit ? $creditLimit->limit_arabianpay_after : $default;
    }
}

if (!function_exists('get_seller_commission')) {
    function get_seller_commission($user_id, $default = 5.00)
    {
        $commission = Approval::where('user_id', $user_id)->latest()->first();
        return $commission ? $commission->comission : $default;
    }
}

if (!function_exists('get_system_commission')) {
    function get_system_commission($default = 0)
    {
        return get_setting('system_commission', $default);
    }
}


if (!function_exists('calculate_order_tax')) {
    function calculate_order_tax($order)
    {
        $totalTax = 0;
        $items = json_decode($order->product_details, true);

        foreach ($items as $item) {
            $product = App\Models\Product::find($item['product_id']);

            if (!$product || !isset($product->tax_type)) {
                continue;
            }

            // Get tax value, use setting fallback if null
            $productTax = $product->tax ?? get_setting('tax', 0);

            // Calculate item total price (sum attribute prices × quantity)
            $attributePrice = collect($item['attributes'])->sum('price');
            $totalPrice = $attributePrice * $item['quantity'];

            if ($product->tax_type === 'percent') {
                $tax = ($productTax / 100) * $totalPrice;
            } else {
                $tax = $productTax * $item['quantity'];
            }

            $totalTax += $tax;
        }

        return $totalTax;
    }
}

if (! function_exists('map_product_details')) {
    function map_product_details(string $jsonDetails): Collection
    {
        $items = json_decode($jsonDetails, true) ?: [];

        return collect($items)->map(function (array $item) {
            // find product
            $product = Product::find($item['product_id']);

            if ($product) {
                // price: first attribute price or fallback to unit_price
                $price = collect($item['attributes'] ?? [])
                    ->pluck('price')
                    ->first()
                    ?: $product->unit_price
                    ?: 0;

                $quantity = $item['quantity'] ?? 0;

                return (object)[
                    'product'    => $product,
                    'quantity'   => $quantity,
                    'price'      => $price,
                    'attributes' => $item['attributes'] ?? [],
                    'total'      => $price * $quantity,
                ];
            }
        });
    }

    if (!function_exists('currentUser')) {
        function currentUser()
        {
            return Auth::user();
        }
    }


    if (!function_exists('getEmployees')) {
        function getEmployees($department = null)
        {
            $user = Auth::user();

            $query = User::where('user_type', 'employee');

            if ($user) {
                // Exclude current logged-in user
                $query->where('id', '!=', $user->id);

                if ($user->user_type !== 'admin') {
                    if ($user->is_manager) {
                        // Manager: see all employees in their department
                        $query->where('department_id', $user->department);
                    } else {
                        // Not manager: see only managers in their department
                        $query->where('is_manager', true)
                            ->where('department_id', $user->department);
                    }
                } elseif ($department !== null) {
                    // Admin with department filter
                    $query->where('department_id', $department);
                }
            }

            return $query->orderBy('first_name')->get();
        }
    }
}


use Illuminate\Support\Facades\Storage;

if (!function_exists('getImageUrl')) {
    function getImageUrl($path)
    {
        // Local path check
        if (Storage::disk('public')->exists($path)) {
            return asset($path);
        }

        // Check if exists on partners subdomain
        $partnersUrl = 'https://partners.araboanpay.com/' . $path;
        if (urlExists($partnersUrl)) {
            return $partnersUrl;
        }

        // Fallback: core domain
        $coreUrl = 'https://core.araboanpay.com/' . $path;
        if (urlExists($coreUrl)) {
            return $coreUrl;
        }

        // Optional fallback image
        return asset('images/no-image.png');
    }

    function urlExists($url)
    {
        try {
            $headers = get_headers($url);
            return strpos($headers[0], '200') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }
}

use App\Services\CreditAssessmentService;

if (! function_exists('get_credit_score')) {
    /**
     * Get credit score for a given user id.
     *
     * @param int $userId
     * @return array
     */
    function get_credit_score(int $userId): array
    {
        $service = new CreditAssessmentService();
        $result = $service->assess($userId);
        return $result['creditScore'] ?? [];
    }
}

use App\Services\RiskAnalyticsService;

/**
 * Get risk score object for a given user or user ID
 *
 * @param int|User $userOrId
 * @return object|null
 */
function get_risk_score($userOrId)
{
    $service = app(RiskAnalyticsService::class);

    if ($userOrId instanceof User) {
        $user = $userOrId;
    } else {
        $user = User::find($userOrId);
        if (!$user) {
            return null;
        }
    }

    return $service->calculateForUser($user);
}

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;  // ← ADDED

/**
 * Convert a Hijri (Islamic) date to a Gregorian date via Aladhan API.
 *
 * Supports either:
 *   hijriToGregorian(1446, 1, 9)
 * or
 *   hijriToGregorian('1446/01/09')
 *
 * @param  int|string  $hYearOrDate  Hijri year or date string "YYYY/MM/DD"
 * @param  int|null    $hMonth       Hijri month (1–12)
 * @param  int|null    $hDay         Hijri day (1–30)
 * @param  int         $adjustment   Optional day-adjustment for Hijri date
 * @return \Carbon\Carbon|null       Gregorian Carbon date or null on failure
 */
if (! function_exists('hijriToGregorian')) {
    function hijriToGregorian($hYearOrDate, int $hMonth = null, int $hDay = null, int $adjustment = 0): ?Carbon
    {
        // parse "YYYY/MM/DD" string if given
        if (is_string($hYearOrDate)) {
            [$hYear, $hMonth, $hDay] = array_map('intval', explode('/', $hYearOrDate));
        } else {
            $hYear = $hYearOrDate;
        }

        // build API date in DD-MM-YYYY
        $dateParam = sprintf('%02d-%02d-%04d', $hDay, $hMonth, $hYear);

        // call Aladhan API
        $response = Http::timeout(5)
            ->get('https://api.aladhan.com/v1/hToG', [
                'date'       => $dateParam,
                'adjustment' => $adjustment,
            ]);

        if (! $response->successful()) {
            return null; // or throw exception if you prefer
        }

        $data = $response->json('data');

        // grab the gregorian date string "DD-MM-YYYY"
        $gregDate = $data['gregorian']['date'] ?? null;

        if (! $gregDate) {
            return null;
        }

        return Carbon::createFromFormat('d-m-Y', $gregDate);
    }
}


if (!function_exists('mediaURL')) {
    function mediaURL($path)
    {
        $path = ltrim($path, '/');
        $relativePath = str_replace('storage/', '', $path);

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($relativePath)) {
            return asset($path);
        }

        $partnersUrl = 'https://partners.arabianpay.net/' . $path;

        if (url_exists($partnersUrl)) {
            return $partnersUrl;
        }

        return asset($path);
    }
}

function url_exists($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($responseCode >= 200 && $responseCode < 400);
}
