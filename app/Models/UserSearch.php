<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSearch extends Model
{
    use HasFactory;

    protected $table = 'user_searches';

    protected $fillable = [
        'user_id',
        'search_term',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
