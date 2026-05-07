<?php

namespace App\Http\Controllers\Requestor;

use App\Http\Controllers\Controller;
use App\Models\SupplyRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RequestController extends Controller
{
    /**
     * Active Requests index — shows all non-draft requests for the current user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $requests = SupplyRequest::where('user_id', $user->id)
            ->whereNotIn('status', ['draft']) // draft is the cart, not an active request
            ->with(['department'])
            ->latest('request_date')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Requestor/Requests/Index', [
            'requests' => $requests,
        ]);
    }

    /**
     * Show a single request with all its details, items, and timeline.
     * Scoped to the current user so they cannot view other users' requests.
     */
    public function show(Request $request, SupplyRequest $supplyRequest)
    {
        $user = $request->user();

        // Security: Only allow the owner to view their request
        abort_if($supplyRequest->user_id !== $user->id, 403);

        $supplyRequest->load([
            'department',
            'approver',
            'items',
            'timelines.performer',
        ]);

        // Find the approver assigned to the user's department
        // The approver is the user with role 'approver' in the same department
        $departmentApprover = \App\Models\User::where('department_id', $user->department_id)
            ->where('role', 'approver')
            ->first();

        return Inertia::render('Requestor/Requests/Show', [
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
}
