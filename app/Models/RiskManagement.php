<?php

namespace App\Models;

use App\Services\CreditAssessmentService;
use App\Services\RiskAnalyticsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RiskManagement extends Model
{

    /**
     * Risk score bands distribution.
     */
    public static function getRiskScoreDistribution()
    {
        return DB::table('risk_management')
            ->select('risk_level as band', DB::raw('COUNT(*) as count'))
            ->groupBy('risk_level')
            ->get();
    }

    /**
     * Default rates by business type for heatmap.
     */
    public static function getDefaultRatesByBusiness()
    {
        $data = DB::table('risk_management')
            ->select('business_type as line', 'default_count')
            ->get()
            ->groupBy('line');

        return $data->map(fn($items, $line) => [
            'name' => $line,
            'data' => $items->pluck('default_count')->toArray(),
        ])->values()->toArray();
    }

    /**
     * Average credit score.
     */
    public static function getAverageCreditScores()
    {
        return cache()->remember('average_credit_score', now()->addHours(1), function () {
            $creditService = new CreditAssessmentService();
            $users = User::has('customer')->get();

            $totalScores = [];

            foreach ($users as $user) {
                try {
                    $creditScore = $creditService->assess($user->id);
                    $totalScores[] = $creditScore['creditScore']['compositeScore'];
                } catch (\Exception $e) {
                    continue;
                }
            }

            $averageScore = count($totalScores) > 0 ? array_sum($totalScores) / count($totalScores) : 0;

            return round($averageScore, 2);
        });
    }


    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'user_id', 'user_id');
    }

    // public static function getDefaultRatesByBusiness()
    // {
    //     $riskLevels = ['low', 'medium', 'high'];

    //     return BusinessType::withCount([
    //         'customers as low_count' => function ($query) {
    //             $query->whereHas('businessType', fn($q) => $q->where('risk_level', 'low'));
    //         },
    //         'customers as medium_count' => function ($query) {
    //             $query->whereHas('businessType', fn($q) => $q->where('risk_level', 'medium'));
    //         },
    //         'customers as high_count' => function ($query) {
    //             $query->whereHas('businessType', fn($q) => $q->where('risk_level', 'high'));
    //         },
    //     ])->get()->map(function ($type) {
    //         return [
    //             'name' => $type->name,
    //             'data' => [
    //                 $type->low_count,
    //                 $type->medium_count,
    //                 $type->high_count
    //             ]
    //         ];
    //     })->toArray();
    // }
}
