<?php

namespace App\Http\Controllers\Approver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Approver\RejectSupplyRequestRequest;
use App\Http\Requests\Approver\UpdateSupplyRequestItemRequest;
use App\Models\SupplyRequest;
use App\Models\SupplyRequestItem;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class RequestController extends Controller
{
    public function __construct(private ApprovalService $approvalService) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', SupplyRequest::class);

        $user = $request->user();

        $requests = SupplyRequest::with([
            'user',
            'user.externalDepartmentReference',
            'department',
            'items.supply',
        ])
            ->where('department_id', $user->department_id)
            ->where('status', SupplyRequest::STATUS_PENDING_APPROVAL)
            ->latest('request_date')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Approver/Requests/Index', [
            'requests' => $requests,
        ]);
    }

    public function show(Request $request, SupplyRequest $supplyRequest)
    {
        Gate::authorize('view', $supplyRequest);

        abort_if($supplyRequest->department_id !== $request->user()->department_id, 403);

        $supplyRequest->load([
            'user',
            'user.externalDepartmentReference',
            'department',
            'approver',
            'items.supply',
            'timelines.performer',
        ]);
        $departmentApprover = User::where('department_id', $supplyRequest->department_id)
            ->where('role', 'approver')
            ->first();

        return Inertia::render('Approver/Requests/Show', [
            'request' => $supplyRequest,
            'departmentApprover' => $departmentApprover,
        ]);
    }

    public function approve(Request $request, SupplyRequest $supplyRequest)
    {
        Gate::authorize('update', $supplyRequest);

        $this->approvalService->approve($supplyRequest, $request->user());

        return back()->with('success', 'Request approved successfully.');
    }

    public function reject(RejectSupplyRequestRequest $request, SupplyRequest $supplyRequest)
    {
        Gate::authorize('update', $supplyRequest);

        $this->approvalService->reject(
            $supplyRequest,
            $request->user(),
            $request->validated('rejection_reason')
        );

        return  back()->with('success', 'Request rejected.');
    }

    public function updateItem(
        UpdateSupplyRequestItemRequest $request,
        SupplyRequest $supplyRequest,
        SupplyRequestItem $item
    ) {
        Gate::authorize('update', $supplyRequest);

        $this->approvalService->updateItemQuantity(
            $supplyRequest,
            $item,
            $request->user(),
            (int) $request->validated('quantity')
        );

        return redirect()->back()->with('success', 'Quantity updated successfully.');
    }

    public function removeItem(Request $request, SupplyRequest $supplyRequest, SupplyRequestItem $item)
    {
        Gate::authorize('update', $supplyRequest);

        $this->approvalService->removeItem($supplyRequest, $item, $request->user());

        return redirect()->back()->with('success', 'Item removed successfully.');
    }
}
