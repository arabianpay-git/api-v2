<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewStore extends Model
{
    protected $table = 'reviews_store';
    protected $fillable = ['user_id', 'store_id', 'order_id', 'rating', 'comment', 'photos', 'status', 'viewed'];
    /**
     * User who owns the address.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
