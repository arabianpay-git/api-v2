<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CustomerCreditLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'simah_limit',
        'limit_arabianpay_after',
        'limit_arabianpay_before',
        'comission',
        'report_simah',
    ];

    protected $casts = [
        'simah_limit' => 'decimal:2',
        'limit_arabianpay_after' => 'decimal:2',
        'limit_arabianpay_before' => 'decimal:2',
        'report_simah' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }


    /**
     * Total vs used ArabianPay credit utilization.
     * (float) get_setting('credit_limit', 0)
     */
    public static function getCreditUtilization()
    {
        $creditPerCustomer = (float) get_setting('credit_limit', 0);

        $customers = \App\Models\Customer::with(['user.orders' => function ($q) {
            $q->where('delivery_status', 'delivered');
        }])->get();

        $totalLimit = $customers->count() * $creditPerCustomer;
        $used = 0;

        foreach ($customers as $customer) {
            $orders = $customer->user->orders ?? [];

            foreach ($orders as $order) {
                $items         = map_product_details($order->product_details);
                $subTotal      = $items->sum('total');
                $shipping      = $order->shipping_cost ?? 0;
                $discount      = $order->coupon_discount ?? 0;
                $tax           = calculate_order_tax($order);

                $base          = $subTotal + $tax + $shipping - $discount;

                $commissionPct     = get_system_commission();
                $commissionAmount  = $base * ($commissionPct / 100);

                $commissionTaxPct  = get_commission_tax();
                $commissionTaxAmt  = $commissionAmount * ($commissionTaxPct / 100);

                $totalAmount       = $base + $commissionAmount + $commissionTaxAmt;

                $used += $totalAmount;
            }
        }

        return [
            'limit'               => $totalLimit,
            'used'                => $used,
            'utilization_percent' => $totalLimit ? round(($used / $totalLimit) * 100, 1) : 0,
        ];
    }
}
