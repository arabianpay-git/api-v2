<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class Order extends Model
{
    use HasFactory, EncryptsAttributes;

    protected $fillable = [
        'assigned_to',
        'user_id',
        'seller_id',
        'pickup_point_id',
        'product_details',
        'shipping_first_name',
        'shipping_last_name',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_city',
        'shipping_state',
        'shipping_country',
        'shipping_postal_code',
        'shipping_type',
        'order_from',
        'payment_type',
        'shipping_cost',
        'payment_status',
        'payment_details',
        'grand_total',
        'coupon_discount',
        'code',
        'tracking',
        'delivery_status',
        'general_status'
    ];

    protected $encryptableAttributes = [
        'shipping_first_name',
        'shipping_last_name',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_city',
        'shipping_state',
        'shipping_country',
        'shipping_postal_code',
        'shipping_type',
        'order_from',
        'payment_type',
        'shipping_cost',
        'grand_total',
        'coupon_discount',
        'code',
        'tracking',
    ];

    protected $casts = [
        'payment_details' => 'array',
    ];


    public function assigned()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function pickupPoint()
    {
        return $this->belongsTo(PickupPoint::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'order_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'order_id');
    }

    public function schedulePayment()
    {
        return $this->hasMany(SchedulePayment::class, 'order_id');
    }

    public function refund()
    {
        return $this->hasMany(RefundRequest::class, 'order_id');
    }

    public function wallet()
    {
        return $this->hasMany(Wallet::class, 'order_id');
    }

    /**
     * Revenue streams: grand_total vs shipping_cost vs coupon_discount over N months.
     */
    public static function getRevenueStreams($range)
    {
        $months = (int)$range;
        $end    = Carbon::now();
        $start  = $end->copy()->subMonths($months - 1)->startOfMonth();

        // build month labels
        $labels = [];
        for ($i = 0; $i < $months; $i++) {
            $labels[] = $start->copy()->addMonths($i)->format('M Y');
        }

        // fetch and key by month
        $raw = DB::table('orders')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%b %Y') as month"),
                DB::raw('SUM(grand_total)        as revenue'),
                DB::raw('SUM(shipping_cost)      as shipping'),
                DB::raw('SUM(coupon_discount)    as discounts')
            )
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('month')
            ->get()
            ->keyBy('month');  // now $raw['Apr 2025']->revenue, etc.

        // build series arrays
        $revenueSeries   = [];
        $shippingSeries  = [];
        $discountSeries  = [];

        foreach ($labels as $m) {
            $row = $raw->get($m);
            $revenueSeries[]  = $row->revenue  ?? 0;
            $shippingSeries[] = $row->shipping ?? 0;
            $discountSeries[] = $row->discounts ?? 0;
        }

        return [
            'months'   => $labels,
            'revenue'  => $revenueSeries,
            'shipping' => $shippingSeries,
            'discounts' => $discountSeries,
        ];
    }


    /**
     * Distribution of order delivery_status.
     */
    public static function getStatusDistribution($range)
    {
        return DB::table('orders')
            ->select('delivery_status as status', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', Carbon::now()->subMonths((int)$range))
            ->groupBy('delivery_status')
            ->get();
    }

    /**
     * Average fulfillment time (in days) per month.
     * Uses updated_at for delivered orders as the delivery timestamp.
     */
    public static function getFulfillmentTimes($range)
    {
        $months = (int)$range;
        $end    = Carbon::now();
        $start  = $end->copy()->subMonths($months - 1)->startOfMonth();

        // build month labels
        $labels = [];
        for ($i = 0; $i < $months; $i++) {
            $labels[] = $start->copy()->addMonths($i)->format('M Y');
        }

        // calculate average days between created_at and updated_at for delivered orders
        $raw = DB::table('orders')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%b %Y') as month"),
                DB::raw('AVG(DATEDIFF(updated_at, created_at)) as avg_days')
            )
            ->where('delivery_status', 'delivered')
            ->whereBetween('updated_at', [$start, $end])
            ->groupBy('month')
            ->pluck('avg_days', 'month')
            ->toArray();

        // build the series, defaulting to 0 if a month is missing
        $series = [];
        foreach ($labels as $m) {
            $series[] = isset($raw[$m])
                ? round($raw[$m], 1)
                : 0;
        }

        return [
            'months' => $labels,
            'series' => $series,
        ];
    }


    /**
     * Payment type distribution.
     */
    public static function getPaymentMethodDistribution($range)
    {
        return DB::table('orders')
            ->select('payment_type as method', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', Carbon::now()->subMonths((int)$range))
            ->groupBy('payment_type')
            ->get();
    }
}
