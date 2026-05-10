<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supply extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_code',
        'item_description',
        'category',
        'unit',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function reference()
    {
        return $this->hasOne(ExternalSupplyReference::class, 'item_code', 'item_code');
    }

    public function requestItems(): HasMany
    {
        return $this->hasMany(SupplyRequestItem::class);
    }
}
