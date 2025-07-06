<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskScore extends Model
{
    protected $fillable = [
        'user_id',
        'risk_score',
        'reason',
    ];

    /**
     * Relationship: RiskScore belongs to a User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
