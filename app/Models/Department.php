<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'external_department_reference_id',
        'code',
        'name',
        'type',
        'area',
        'cost_center',
        'cost_center_source',
        'is_custom',
    ];

    protected $casts = [
        'is_custom' => 'boolean',
    ];

    public function externalReference(): BelongsTo
    {
        return $this->belongsTo(ExternalDepartmentReference::class);
    }

    public function externalRef(): BelongsTo
    {
        return $this->externalReference();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
