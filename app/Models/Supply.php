<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supply extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_code',
        'item_description', 
        'category',
        'unit',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Updated appends for Vue
    protected $appends = [
        'display_description', 
        'available_stocks',
        'allocatable_stocks'
    ];

    public function reference()
    {
        return $this->belongsTo(ExternalSupplyReference::class, 'item_code', 'item_code');
    }

    // --- Accessors for Description ---
    public function getDisplayDescriptionAttribute()
    {
        return $this->reference ? $this->reference->item_description : $this->item_description;
    }

    // --- Accessors for Real-Time Stocks ---
    public function getAvailableStocksAttribute()
    {
        return $this->reference ? $this->reference->available_stocks : 0;
    }

    public function getAllocatableStocksAttribute()
    {
        return $this->reference ? $this->reference->allocatable_stocks : 0;
    }
}