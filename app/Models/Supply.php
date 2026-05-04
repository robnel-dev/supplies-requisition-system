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

    // These append the calculated data directly into the Vue component
    protected $appends = [
        'display_description', 
        'available_stocks', 
        'allocatable_stocks'
    ]; 

    public function reference()
    {
        // explicitly tell Laravel to match 'item_code' to 'item_code'
        return $this->hasOne(ExternalSupplyReference::class, 'item_code', 'item_code');
    }

    // --- Accessors ---
    public function getDisplayDescriptionAttribute()
    {
        return $this->reference ? $this->reference->item_description : $this->item_description;
    }

    public function getAvailableStocksAttribute()
    {
        return $this->reference ? $this->reference->stock_quantity : 0;
    }

    public function getAllocatableStocksAttribute()
    {
        return $this->reference ? $this->reference->allocatable_quantity : 0;
    }
}