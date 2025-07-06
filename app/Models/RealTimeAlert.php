<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RealTimeAlert extends Model
{
    protected $fillable = [
        'user_id',
        'risk_score',
        'suspicious_activity',
        'transaction_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
