<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCardToken extends Model
{
    protected $fillable = [
        'user_id','token','tran_ref','brand','last4','exp_month','exp_year'
    ];
}
