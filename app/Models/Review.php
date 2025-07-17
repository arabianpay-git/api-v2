<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table = 'reviews';
    protected $fillable = ['user_id', 'product_id', 'order_id', 'rating', 'comment', 'photos', 'status', 'viewed'];

    /**
     * User who owns the address.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
