<?php

namespace App\Http\Controllers;

use App\Models\SupplyRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        return Inertia::render('Dashboard', [
            'approverStats' => $this->approverStats($request),
        ]);
    }

    private function approverStats(Request $request): ?array
    {
        $user = $request->user();

        if ($user->role !== 'approver') {
            return null;
        }

        $monthRange = [now()->startOfMonth(), now()->endOfMonth()];

        return [
            'pending' => SupplyRequest::where('department_id', $user->department_id)
                ->where('status', SupplyRequest::STATUS_PENDING_APPROVAL)
                ->count(),
            'approved' => SupplyRequest::where('department_id', $user->department_id)
                ->where('status', SupplyRequest::STATUS_APPROVED)
                ->whereBetween('manager_approved_at', $monthRange)
                ->count(),
            'rejected' => SupplyRequest::where('department_id', $user->department_id)
                ->where('status', SupplyRequest::STATUS_REJECTED)
                ->whereBetween('updated_at', $monthRange)
                ->count(),
        ];
    }
}
