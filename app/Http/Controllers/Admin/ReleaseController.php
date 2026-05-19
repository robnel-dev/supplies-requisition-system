<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Release\RejectReleaseRequest;
use App\Http\Requests\Admin\Release\UpdateReleaseRequest;
use App\Models\SupplyRequest;
use App\Services\ReleaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ReleaseController extends Controller
{
    public function __construct(private ReleaseService $releaseService) {}

    public function index(Request $request)
    {
        Gate::authorize('viewReleases', SupplyRequest::class);

        $search = trim((string) $request->input('search', ''));
        $status = $this->validatedStatus($request->input('status', ''));

        $requests = $this->releaseQueueQuery($search, $status)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Releases/Index', [
            'requests' => $requests,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'stats' => [
                'pendingRelease' => SupplyRequest::where('status', SupplyRequest::STATUS_APPROVED)->count(),
                'released' => SupplyRequest::where('status', SupplyRequest::STATUS_RELEASED)->count(),
            ],
        ]);
    }

    public function show(SupplyRequest $supplyRequest)
    {
        Gate::authorize('viewRelease', $supplyRequest);

        $supplyRequest->load($this->requestDetailRelations());

        return Inertia::render('Admin/Releases/Show', [
            'request' => $supplyRequest,
        ]);
    }

    public function update(UpdateReleaseRequest $request, SupplyRequest $supplyRequest)
    {
        Gate::authorize('manageRelease', $supplyRequest);

        $this->releaseService->updateDetails(
            $supplyRequest,
            $request->user(),
            $request->validated()
        );

        return back()->with('success', 'Release details saved.');
    }

    public function release(Request $request, SupplyRequest $supplyRequest)
    {
        Gate::authorize('manageRelease', $supplyRequest);

        $this->releaseService->release($supplyRequest, $request->user());

        return back()->with('success', 'Request released successfully.');
    }

    public function reject(RejectReleaseRequest $request, SupplyRequest $supplyRequest)
    {
        Gate::authorize('manageRelease', $supplyRequest);

        $this->releaseService->reject(
            $supplyRequest,
            $request->user(),
            $request->validated('rejection_reason')
        );

        return redirect()->route('admin.releases.index')
            ->with('success', 'Request rejected.');
    }

    public function archive(Request $request, SupplyRequest $supplyRequest)
    {
        Gate::authorize('manageRelease', $supplyRequest);

        $this->releaseService->archive($supplyRequest, $request->user());

        return redirect()->route('admin.releases.index')
            ->with('success', 'Request archived.');
    }

    private function releaseQueueQuery(string $search, string $status): Builder
    {
        return SupplyRequest::query()
            ->with($this->requestDisplayRelations())
            ->withCount('items')
            ->whereIn('status', $this->releaseStatuses())
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('transaction_id', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                            ->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('department', fn (Builder $departmentQuery) => $departmentQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%"))
                        ->orWhereHas('user.externalDepartmentReference', fn (Builder $storeQuery) => $storeQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('department_code', 'like', "%{$search}%"));
                });
            })
            ->orderByRaw(
                'case when status = ? then 0 else 1 end',
                [SupplyRequest::STATUS_APPROVED]
            )
            ->latest('request_date');
    }

    private function requestDisplayRelations(): array
    {
        return [
            'department:id,name,type,code',
            'user:id,name,external_department_reference_id',
            'user.externalDepartmentReference:id,name,department_code',
        ];
    }

    private function requestDetailRelations(): array
    {
        return [
            ...$this->requestDisplayRelations(),
            'approver:id,name',
            'managerApprover:id,name',
            'hrAdminReleaser:id,name',
            'items.supply',
            'timelines' => fn ($query) => $query
                ->where('action', '!=', 'item_updated')
                ->latest(),
            'timelines.performer:id,name',
        ];
    }

    private function validatedStatus(mixed $status): string
    {
        $status = (string) $status;

        return in_array($status, $this->releaseStatuses(), true) ? $status : '';
    }

    private function releaseStatuses(): array
    {
        return [
            SupplyRequest::STATUS_APPROVED,
            SupplyRequest::STATUS_RELEASED,
        ];
    }
}
