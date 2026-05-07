<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'supply_request_id',
        'supply_id',
        'item_code',
        'item_description',
        'item_unit',
        'quantity',
        'original_quantity',
        'budget_type',
        'allocated_quantity',
        'balance',
    ];

    protected $casts = [
        'quantity'           => 'integer',
        'original_quantity'  => 'integer',
        'allocated_quantity' => 'integer',
        'balance'            => 'integer',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(SupplyRequest::class, 'supply_request_id');
    }

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }
}
