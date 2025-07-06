<?php

namespace App\Models;

use App\Traits\LogsModelActions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;

class SupportTicket extends Model
{
    use HasFactory, LogsModelActions, EncryptsAttributes;

    protected static $logAttributes = ['status', 'amount', 'due_date'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'support_ticket';

    protected $fillable = [
        'user_id',
        'assigned_to',
        'ticket_number',
        'subject',
        'details',
        'files',
        'reply',
        'status'
    ];

    protected $encryptableAttributes = [
        'ticket_number',
        'subject',
        'details',
        'reply',
    ];

    protected $casts = [
        'files' => 'array',
    ];

    // In SupportTicket model
    public function assigned()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
