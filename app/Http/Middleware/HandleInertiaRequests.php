<?php

namespace App\Http\Middleware;

use App\Models\SupplyRequest;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id'   => $request->user()->id,
                    'name' => $request->user()->name,
                    'role' => $request->user()->role,
                    // Role flags keep Vue templates simple and avoid repeating role strings.
                    'can' => [
                        'manage_system'    => $request->user()->role === 'hr_admin',
                        'approve_requests' => $request->user()->role === 'approver',
                        'is_employee'      => $request->user()->role === 'requestor',
                    ],
                ] : null,
            ],
            // Badge counts shared to all pages for sidebar/topbar badges.
            // Uses lazy evaluation so they only run when a page is actually served.
            'badgeCounts' => fn() => $this->resolveBadgeCounts($request),
        ];
    }

    /**
     * Resolve actionable badge counts based on the authenticated user's role.
     * Returns an empty array for guests.
     */
    private function resolveBadgeCounts(Request $request): array
    {
        $user = $request->user();

        if (! $user) {
            return [];
        }

        return match ($user->role) {
            'approver'  => $this->approverBadges($user),
            'hr_admin'  => $this->adminBadges(),
            'requestor' => $this->requestorBadges($user),
            default     => [],
        };
    }

    private function approverBadges($user): array
    {
        $pending = SupplyRequest::where('department_id', $user->department_id)
            ->where('status', SupplyRequest::STATUS_PENDING_APPROVAL)
            ->count();

        return [
            'approvals'  => $pending,
            'total'      => $pending,
        ];
    }

    private function adminBadges(): array
    {
        $pendingRelease = SupplyRequest::where('status', SupplyRequest::STATUS_APPROVED)->count();

        return [
            'pendingRelease' => $pendingRelease,
            'total'          => $pendingRelease,
        ];
    }

    private function requestorBadges($user): array
    {
        // Notify requestor about approved (ready for pickup) requests
        $approved = SupplyRequest::where('user_id', $user->id)
            ->where('status', SupplyRequest::STATUS_APPROVED)
            ->count();

        $pending = SupplyRequest::where('user_id', $user->id)
            ->where('status', SupplyRequest::STATUS_PENDING_APPROVAL)
            ->count();

        return [
            'approved' => $approved,
            'pending'  => $pending,
            'total'    => $approved,  // Badge highlights approved (needs attention)
        ];
    }
}
