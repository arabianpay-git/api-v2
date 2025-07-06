<?php

namespace App\Models;

use App\Traits\LogsModelActions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;
    use LogsModelActions;

    protected static $logAttributes = ['status', 'amount', 'due_date'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'media';

    protected $fillable = [
        'user_id',
        'name',
        'file_name',
        'mime_type',
        'size',
        'disk',
        'folder',
        'custom_properties'
    ];

    protected $casts = [
        'custom_properties' => 'array',
    ];
}
