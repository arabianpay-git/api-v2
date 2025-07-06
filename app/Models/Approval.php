<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class Approval extends Model
{
    use EncryptsAttributes;

    protected $fillable = [
        'user_id',
        'employee_id',
        'commission',
        'reason',
        'contract',
        'fahman_score',
        'payment_schedule',
    ];

    protected $encryptableAttributes = [
        'commission',
        'reason',
        'fahman_score',
        'payment_schedule',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
