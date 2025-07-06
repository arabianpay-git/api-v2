<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'seller_id',
        'order_id',
        'instalment_id',
        'transaction_type',
        'amount',
        'balance_after',
        'status',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function instalment()
    {
        return $this->belongsTo(InstalmentPlan::class, 'instalment_id');
    }

    /**
     * Balance trend (average) over N months.
     */
    public static function getBalanceTrends($range)
    {
        $months = (int)$range;
        $end    = Carbon::now();
        $start  = $end->copy()->subMonths($months - 1)->startOfMonth();

        $labels = [];
        $types = ['user_payment', 'loan_disbursment', 'seller_payment'];
        $series = [
            'balance'           => [],
            'user_payment'      => [],
            'loan_disbursment'  => [],
            'seller_payment'    => [],
        ];

        for ($i = 0; $i < $months; $i++) {
            $labels[] = $start->copy()->addMonths($i)->format('M Y');
        }

        // Get overall avg balance per month
        $overall = DB::table('wallets')
            ->select(DB::raw("DATE_FORMAT(created_at, '%b %Y') as month"), DB::raw('AVG(balance_after) as bal'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('month')
            ->pluck('bal', 'month')
            ->toArray();

        // Get avg balance per transaction type per month
        $raw = DB::table('wallets')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%b %Y') as month"),
                'transaction_type',
                DB::raw('AVG(balance_after) as bal')
            )
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('transaction_type', $types)
            ->groupBy('month', 'transaction_type')
            ->get();

        foreach ($labels as $month) {
            // overall balance
            $series['balance'][] = isset($overall[$month]) ? round($overall[$month], 2) : 0;

            // per transaction type
            foreach ($types as $type) {
                $match = $raw->firstWhere(fn($row) => $row->month === $month && $row->transaction_type === $type);
                $series[$type][] = $match ? round($match->bal, 2) : 0;
            }
        }

        return collect($labels)->map(function ($label, $index) use ($series) {
            return [
                'month'            => $label,
                'balance'          => $series['balance'][$index],
                'user_payment'     => $series['user_payment'][$index],
                'loan_disbursment' => $series['loan_disbursment'][$index],
                'seller_payment'   => $series['seller_payment'][$index],
            ];
        });
    }
}
