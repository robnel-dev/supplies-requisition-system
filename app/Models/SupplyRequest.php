<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\RequestTimeline;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplyRequest extends Model
{
    use HasFactory;


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

    protected $casts = [
        'request_date'         => 'datetime',
        'manager_approved_at'  => 'datetime',
        'hr_admin_released_at' => 'datetime',
    ];

    // --- Status constants for clarity ---
    const STATUS_DRAFT            = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED         = 'approved';
    const STATUS_REJECTED         = 'rejected';
    const STATUS_RELEASED         = 'released';
    const STATUS_CANCELLED        = 'cancelled';
    const STATUS_ARCHIVED         = 'archived';

    // --- Relationships ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplyRequestItem::class);
    }

    public function timelines(): HasMany
    {
        return $this->hasMany(RequestTimeline::class)->orderBy('created_at', 'asc');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function managerApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_approved_by');
    }

    public function hrAdminReleaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_admin_released_by');
    }
}
