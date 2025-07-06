<?php

namespace App\Models;

use App\Traits\LogsModelActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferRequest extends Model
{
    use LogsModelActions;
    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'model_type',
        'model_id',
        'description',
        'status',
    ];

    /**
     * Get the sender user.
     */
    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * Get the receiver user.
     */
    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    /**
     * Get the polymorphic related model (User, Order, etc).
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
