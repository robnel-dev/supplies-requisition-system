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
        // Match local item_code to external item_code
        return $this->hasOne(ExternalSupplyReference::class, 'item_code', 'item_code');
    }

    /**
     * A supply can appear in many request items.
     * Used for the "in-use" check before deletion.
     */
    public function requestItems(): HasMany
    {
        return $this->hasMany(SupplyRequestItem::class);
    }
}
