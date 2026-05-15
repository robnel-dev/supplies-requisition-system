<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExternalSupplyReference extends Model
{
    use HasFactory;

    protected $connection = 'external_mysql';

    protected $fillable = [
        'company_no',
        'warehouse_location',
        'item_code',
        'item_description',
        'unit_of_measure',
        'stock_quantity',
        'allocated_quantity',
        'allocatable_quantity',
        'MBORTY',
        'MBSTAT',
    ];

    public function supply()
    {
        return $this->hasOne(Supply::class, 'item_code', 'item_code');
    }
}
