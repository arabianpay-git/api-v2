<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;
use Spatie\Permission\Traits\HasRoles;

class Department extends Model
{
    use EncryptsAttributes, HasRoles;

    protected $fillable = ['name'];
    protected $encryptableAttributes = ['name'];
    protected $guard_name = 'web';

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
