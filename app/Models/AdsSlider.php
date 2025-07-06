<?php

namespace App\Models;

use App\Traits\LogsModelActions;
use Illuminate\Database\Eloquent\Model;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class AdsSlider extends Model
{
    use LogsModelActions, EncryptsAttributes;

    protected $fillable = ['name'];
    protected $encryptableAttributes = ['name'];
    protected array $translatable = ['name'];

    protected static $logAttributes = ['status', 'amount', 'due_date'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'ads_slider';

    protected $table = 'ads_slider';


}
