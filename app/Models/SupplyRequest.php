<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplyRequest extends Model
{
    protected $fillable = [
        'transaction_id',
        'user_id',
        'department_id',
        'approver_id',
        'status',
        'request_date',
        'm3_ro_number',
        'm3_dr_number',
        'manager_approved_by',
        'manager_approved_at',
        'manager_notes',
        'hr_admin_released_by',
        'hr_admin_released_at',
        'hr_admin_notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function items()
    {
        return $this->hasMany(SupplyRequestItem::class);
    }

    protected $casts = [
        'request_date' => 'datetime',
    ];
}
