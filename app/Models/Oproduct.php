<?php 
// app/Models/OProduct.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OProduct extends Model
{
    protected $table = 'oproducts';
    public $timestamps = false;

    public function ocategory()
    {
        return $this->belongsTo(Ocategory::class, 'category_id');
    }
}
