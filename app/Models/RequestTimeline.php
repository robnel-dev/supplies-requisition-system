<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestTimeline extends Model
{
    protected $fillable = [
        'supply_request_id',
        'action',
        'description',
        'performed_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(SupplyRequest::class, 'supply_request_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
