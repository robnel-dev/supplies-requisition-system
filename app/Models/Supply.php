<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supply extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_code',
        'item_name',
        'item_description',
        'category',
        'unit',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Append these so Vue can read them directly as if they were columns in the supplies table!
    protected $appends = [
        'display_name',
        'display_description',
        'available_stocks',
        'allocatable_stocks'
    ];

    public function reference()
    {
        return $this->belongsTo(ExternalSupplyReference::class, 'item_code', 'item_code');
    }

    // --- Accessors for Name & Description ---
    public function getDisplayNameAttribute()
    {
        return $this->reference ? $this->reference->item_name : $this->item_name;
    }

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
