<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Supply;
use App\Models\SupplyRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'dashboardStats' => $this->getStatsForRole($user),
            'recentActivity' => $this->getRecentActivity($user),
        ]);
    }

    // ─── Role-based stats dispatcher ─────────────────────────────────────────

    private function getStatsForRole(User $user): array
    {
        return match ($user->role) {
            'hr_admin'  => $this->adminStats(),
            'approver'  => $this->approverStats($user),
            'requestor' => $this->requestorStats($user),
            default     => [],
        };
    }

    // ─── HR Admin Stats ───────────────────────────────────────────────────────

    private function adminStats(): array
    {
        $monthRange = [now()->startOfMonth(), now()->endOfMonth()];
        $prevMonthRange = [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()];

        $releasedThisMonth = SupplyRequest::where('status', SupplyRequest::STATUS_RELEASED)
            ->whereBetween('hr_admin_released_at', $monthRange)
            ->count();

        $releasedLastMonth = SupplyRequest::where('status', SupplyRequest::STATUS_RELEASED)
            ->whereBetween('hr_admin_released_at', $prevMonthRange)
            ->count();

        $pendingRelease = SupplyRequest::where('status', SupplyRequest::STATUS_APPROVED)->count();

        // Supply stock summary (reference from external system)
        $totalSupplies = Supply::where('is_active', true)->count();

        // Department activity
        $activeDepartments = Department::has('users')->count();
        $totalUsers = User::count();

        // Requests by status
        $requestsByStatus = SupplyRequest::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Recent releases
        $recentReleases = SupplyRequest::with(['user:id,name', 'department:id,name'])
            ->where('status', SupplyRequest::STATUS_RELEASED)
            ->latest('hr_admin_released_at')
            ->limit(5)
            ->get(['id', 'transaction_id', 'user_id', 'department_id', 'hr_admin_released_at', 'status']);

        return [
            'pendingRelease'      => $pendingRelease,
            'releasedThisMonth'   => $releasedThisMonth,
            'releasedLastMonth'   => $releasedLastMonth,
            'totalSupplies'       => $totalSupplies,
            'activeDepartments'   => $activeDepartments,
            'totalUsers'          => $totalUsers,
            'requestsByStatus'    => $requestsByStatus,
            'recentReleases'      => $recentReleases,
        ];
    }

    // ─── Approver Stats ───────────────────────────────────────────────────────

    private function approverStats(User $user): array
    {
        $monthRange = [now()->startOfMonth(), now()->endOfMonth()];

        $pending = SupplyRequest::where('department_id', $user->department_id)
            ->where('status', SupplyRequest::STATUS_PENDING_APPROVAL)
            ->count();

        $approvedThisMonth = SupplyRequest::where('department_id', $user->department_id)
            ->where('status', SupplyRequest::STATUS_APPROVED)
            ->whereBetween('manager_approved_at', $monthRange)
            ->count();

        $rejectedThisMonth = SupplyRequest::where('department_id', $user->department_id)
            ->where('status', SupplyRequest::STATUS_REJECTED)
            ->whereBetween('updated_at', $monthRange)
            ->count();

        $totalHandled = SupplyRequest::where('department_id', $user->department_id)
            ->whereIn('status', [
                SupplyRequest::STATUS_APPROVED,
                SupplyRequest::STATUS_REJECTED,
                SupplyRequest::STATUS_RELEASED,
            ])
            ->count();

        // Approval rate
        $totalDecided = $approvedThisMonth + $rejectedThisMonth;
        $approvalRate = $totalDecided > 0 ? round(($approvedThisMonth / $totalDecided) * 100) : 0;

        // Pending queue (latest 5)
        $pendingRequests = SupplyRequest::with(['user:id,name', 'items'])
            ->where('department_id', $user->department_id)
            ->where('status', SupplyRequest::STATUS_PENDING_APPROVAL)
            ->latest('request_date')
            ->limit(5)
            ->get(['id', 'transaction_id', 'user_id', 'request_date', 'status']);

        return [
            'pending'         => $pending,
            'approvedThisMonth' => $approvedThisMonth,
            'rejectedThisMonth' => $rejectedThisMonth,
            'totalHandled'    => $totalHandled,
            'approvalRate'    => $approvalRate,
            'pendingRequests' => $pendingRequests,
        ];
    }

    // ─── Requestor Stats ──────────────────────────────────────────────────────

    private function requestorStats(User $user): array
    {
        $allRequests = SupplyRequest::where('user_id', $user->id)->get(['status', 'created_at', 'updated_at']);

        $draft          = $allRequests->where('status', SupplyRequest::STATUS_DRAFT)->count();
        $pending        = $allRequests->where('status', SupplyRequest::STATUS_PENDING_APPROVAL)->count();
        $approved       = $allRequests->where('status', SupplyRequest::STATUS_APPROVED)->count();
        $released       = $allRequests->where('status', SupplyRequest::STATUS_RELEASED)->count();
        $rejected       = $allRequests->where('status', SupplyRequest::STATUS_REJECTED)->count();
        $cancelled      = $allRequests->where('status', SupplyRequest::STATUS_CANCELLED)->count();
        $total          = $allRequests->count();

        // Recent requests
        $recentRequests = SupplyRequest::with(['department:id,name', 'items'])
            ->where('user_id', $user->id)
            ->latest('updated_at')
            ->limit(5)
            ->get(['id', 'transaction_id', 'department_id', 'status', 'request_date', 'updated_at']);

        // Draft in cart
        $draftRequest = SupplyRequest::where('user_id', $user->id)
            ->where('status', SupplyRequest::STATUS_DRAFT)
            ->with('items')
            ->latest()
            ->first(['id', 'transaction_id', 'status']);

        return [
            'draft'          => $draft,
            'pending'        => $pending,
            'approved'       => $approved,
            'released'       => $released,
            'rejected'       => $rejected,
            'cancelled'      => $cancelled,
            'total'          => $total,
            'recentRequests' => $recentRequests,
            'hasDraft'       => $draftRequest !== null,
            'draftItemCount' => $draftRequest?->items->count() ?? 0,
        ];
    }

    // ─── Recent Activity (shared across roles) ────────────────────────────────

    private function getRecentActivity(User $user): array
    {
        $query = SupplyRequest::with(['user:id,name', 'department:id,name'])
            ->latest('updated_at')
            ->limit(8);

        if ($user->role === 'requestor') {
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'approver') {
            $query->where('department_id', $user->department_id);
        }
        // hr_admin sees all

        return $query->get(['id', 'transaction_id', 'user_id', 'department_id', 'status', 'updated_at'])
            ->toArray();
    }
}
