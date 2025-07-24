<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SchedulePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'assigned_to',
        'user_id',
        'seller_id',
        'order_id',
        'instalment_number',
        'due_date',
        'instalment_amount',
        'principle_amount',
        'late_fee',
        'subscription_fee',
        'shipping_amount',
        'additional_amount',
        'difference_amount',
        'deducted_amount',
        'is_late',
        'late_days',
        'payment_status',
    ];

    protected $casts = [
        'due_date' => 'date',
        'is_late' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    // Relations
    public function assigned()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Payment status distribution.
     */
    public static function getPaymentStatusDistribution()
    {
        return DB::table('schedule_payments')
            ->select('payment_status as status', DB::raw('COUNT(*) as count'))
            ->groupBy('payment_status')
            ->get();
    }

    /**
     * Overdue instalment trend + current delinquency rate.
     */
    public static function getOverdueTrend($range)
    {
        $months = (int)$range;
        $end    = Carbon::now();
        $start  = $end->copy()->subMonths($months - 1)->startOfMonth();

        $labels = [];
        $series = [];
        for ($i = 0; $i < $months; $i++) {
            $labels[] = $start->copy()->addMonths($i)->format('M Y');
        }

        $raw = DB::table('schedule_payments')
            ->select(DB::raw("DATE_FORMAT(due_date, '%b %Y') as month"), DB::raw('COUNT(*) as total'))
            ->where('due_date', '<', $end)
            ->where('payment_status', '!=', 'paid')
            ->whereBetween('due_date', [$start, $end])
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        foreach ($labels as $m) {
            $series[] = $raw[$m] ?? 0;
        }

        $total   = DB::table('schedule_payments')->count();
        $overdue = DB::table('schedule_payments')->where('due_date', '<', $end)->where('payment_status', '!=', 'paid')->count();
        $rate    = $total ? round($overdue / $total * 100, 1) : 0;

        return ['months' => $labels, 'series' => $series, 'rate' => $rate];
    }

    public function transaction()
    {
        return $this->belongsTo(TransactionOrder::class, 'transaction_id');
    }
}
