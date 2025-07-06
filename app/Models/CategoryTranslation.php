<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class CategoryTranslation extends Model
{
    use EncryptsAttributes;

    public $timestamps = false;
    protected $fillable = ['locale', 'name', 'meta_title', 'meta_description'];
    protected $encryptableAttributes = ['name'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
