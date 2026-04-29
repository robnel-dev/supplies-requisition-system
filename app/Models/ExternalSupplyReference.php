<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExternalSupplyReference extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_code', 'item_name', 'item_description', 
        'available_stocks', 'allocatable_stocks'
    ];

    public function supply()
    {
        return $this->hasOne(Supply::class, 'item_code', 'item_code');
    }
}