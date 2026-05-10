<?php

namespace App\Http\Controllers\Requestor;

use App\Http\Controllers\Controller;
use App\Models\SupplyRequest;
use App\Services\CartService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RequestController extends Controller
{
    public function __construct(private CartService $cartService) {}

    /**
     * Active Requests — only pending_approval, approved, released.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $requests = SupplyRequest::where('user_id', $user->id)
            ->whereIn('status', [
                SupplyRequest::STATUS_PENDING_APPROVAL,
                SupplyRequest::STATUS_APPROVED,
                SupplyRequest::STATUS_RELEASED,
            ])
            ->with([
                'department',
                'user:id,name,external_department_reference_id',
                'user.externalDepartmentReference:id,name',
            ])
            ->latest('request_date')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Requestor/ActiveRequests/Index', [
            'requests' => $requests,
        ]);
    }

    /**
     * Archived Requests — rejected, cancelled, archived statuses.
     */
    public function archived(Request $request)
    {
        $user = $request->user();

        $requests = SupplyRequest::where('user_id', $user->id)
            ->whereIn('status', [
                SupplyRequest::STATUS_REJECTED,
                SupplyRequest::STATUS_CANCELLED,
                SupplyRequest::STATUS_ARCHIVED,
            ])
            ->with([
                'department',
                'user:id,name,external_department_reference_id',
                'user.externalDepartmentReference:id,name',
            ])
            ->latest('request_date')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Requestor/ArchivedRequests/Index', [
            'requests' => $requests,
        ]);
    }

    /**
     * Show a single active request.
     */
    public function show(Request $request, SupplyRequest $supplyRequest)
    {
        $user = $request->user();

        abort_if($supplyRequest->user_id !== $user->id, 403);

        $supplyRequest->load([
            'department',
            'user:id,name,external_department_reference_id',
            'user.externalDepartmentReference:id,name',
            'approver',
            'items',
            'timelines.performer',
        ]);

        $departmentApprover = \App\Models\User::where('department_id', $user->department_id)
            ->where('role', 'approver')
            ->first();

        return Inertia::render('Requestor/ActiveRequests/Show', [
            'supplyRequest'      => $supplyRequest,
            'departmentApprover' => $departmentApprover,
        ]);
    }

    /**
     * Show a single archived request.
     */
    public function showArchived(Request $request, SupplyRequest $supplyRequest)
    {
        $user = $request->user();

        abort_if($supplyRequest->user_id !== $user->id, 403);

        $supplyRequest->load([
            'department',
            'user:id,name,external_department_reference_id',
            'user.externalDepartmentReference:id,name',
            'approver',
            'items',
            'timelines.performer',
        ]);

        $departmentApprover = \App\Models\User::where('department_id', $user->department_id)
            ->where('role', 'approver')
            ->first();

        return Inertia::render('Requestor/ArchivedRequests/Show', [
            'supplyRequest'      => $supplyRequest,
            'departmentApprover' => $departmentApprover,
        ]);
    }

    /**
     * Cancel a request — only allowed when status is pending_approval.
     */
    public function cancel(Request $request, SupplyRequest $supplyRequest)
    {
        $user = $request->user();

        abort_if($supplyRequest->user_id !== $user->id, 403);

        if ($supplyRequest->status !== SupplyRequest::STATUS_PENDING_APPROVAL) {
            return back()->withErrors(['error' => 'Only pending requests can be cancelled.']);
        }

        $supplyRequest->update(['status' => SupplyRequest::STATUS_CANCELLED]);

        $supplyRequest->timelines()->create([
            'action'       => 'cancelled',
            'description'  => 'Request was cancelled by the requestor.',
            'performed_by' => $user->id,
        ]);

        return redirect()->route('requestor.requests.index')
            ->with('success', 'Request cancelled successfully.');
    }

    public function reopen(Request $request, SupplyRequest $supplyRequest)
    {
        $user = $request->user();

        abort_if($supplyRequest->user_id !== $user->id, 403);

        if ($supplyRequest->status !== SupplyRequest::STATUS_PENDING_APPROVAL) {
            return back()->withErrors(['reopen' => 'Only requests pending approval can be edited.']);
        }

        try {
            $this->cartService->reopenForEdit($user, $supplyRequest);
        } catch (\Exception $e) {
            // Return the real error message to the Vue page
            return back()->withErrors(['reopen' => $e->getMessage()]);
        }

        return redirect()->route('requestor.catalog.index')
            ->with('success', 'Request reopened for editing. Make your changes and re-submit when ready.');
    }
}
