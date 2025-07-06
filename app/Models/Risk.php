<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Risk extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'risk_id',
        'description',
        'type',
        'entity',
        'score',
        'status',
        'action',
        'owner',
    ];

    protected $casts = [
        'score' => 'float',
        'risk_id' => 'string',
    ];

    // Relationship to User
    public function user()
    {
        return $this->belongsTo(User::class, 'owner');
    }

    // Auto-generate UUID for risk_id
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->risk_id)) {
                $model->risk_id = (string) Str::uuid();
            }
        });
    }
}
