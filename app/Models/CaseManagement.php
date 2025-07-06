<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class CaseManagement extends Model
{
    use EncryptsAttributes;

    protected $table = 'case_management';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'documents',
    ];

    protected $encryptableAttributes = [
        'title',
        'description',
    ];

    protected $encryptableCasts = [
        'due_date' => 'datetime',
    ];

    protected $casts = [
        'documents' => 'array',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
