<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class NafathVerification extends Model
{
    protected $fillable = [
        'national_id',
        'phone_number',
        'trans_id',
        'random',
        'status',
        'error_code',
        'nafath_response',
        'wathiq_status',
        'reject_reason',
        'verified_at',
    ];

    protected $casts = [
        'nafath_response' => 'array',
        'wathiq_status' => 'string',
    ];

    // Encrypt national_id, hash separate column
    public function setNationalIdAttribute($value)
    {
        $this->attributes['iqama_hash']  = Hash::make($value);
        $this->attributes['national_id'] = encrypt($value);
    }

    public function getNationalIdAttribute($value)
    {
        return decrypt($value);
    }

    public function getNafathResponseAttribute($value)
    {
        return json_decode($value, true);
    }
}
