<?php

namespace App\Models;

use App\Traits\LogsModelActions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RefundRequest extends Model
{
    use HasFactory;
    use LogsModelActions;

    protected static $logAttributes = ['status', 'amount', 'due_date'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'refund_request';

    protected $fillable = [
        'assigned_to',
        'user_id',
        'seller_id',
        'order_id',
        'seller_approval',
        'admin_approval',
        'refund_amount',
        'reason',
        'reject_reason',
        'refund_status',
    ];

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
     * Refunds: requested vs approved vs rejected.
     */
    public static function getRefundAnalysis()
    {
        return DB::table('refund_requests')
            ->select('refund_status as status', DB::raw('COUNT(*) as count'), DB::raw('SUM(refund_amount) as total'))
            ->groupBy('refund_status')
            ->get();
    }
}
