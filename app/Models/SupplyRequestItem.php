<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplyRequestItem extends Model
{
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

    public function request()
    {
        return $this->belongsTo(SupplyRequest::class, 'supply_request_id');
    }
    public function supply()
    {
        return $this->belongsTo(Supply::class);
    }
}
