<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class ExternalDepartmentReference extends Model
{
    protected $fillable = [
        'external_id',
        'company_code',
        'department_code',
        'name',
        'cost_center',
        'branch',
        'area',
        'remarks',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeHeadOffice($query)
    {
        return $query->where('branch', 'Head Office');
    }

    public function scopeStore($query)
    {
        return $query->where('branch', 'Store');
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function departmentType(): string
    {
        return $this->branch === 'Head Office' ? 'head_office' : 'store';
    }

    public static function getAreas(): array
    {
        return self::active()
            ->store()
            ->whereNotNull('area')
            ->distinct()
            ->orderBy('area')
            ->pluck('area')
            ->toArray();
    }
}
