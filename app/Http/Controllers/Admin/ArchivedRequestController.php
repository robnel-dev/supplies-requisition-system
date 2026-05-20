<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupplyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ArchivedRequestController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewArchivedRequests', SupplyRequest::class);

        $search = trim((string) $request->input('search', ''));

        $requests = $this->archivedRequestQuery($request, $search)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Archived/Index', [
            'requests' => $requests,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(SupplyRequest $supplyRequest)
    {
        Gate::authorize('viewArchivedRequest', $supplyRequest);

        $supplyRequest->load($this->requestDetailRelations());

        return Inertia::render('Admin/Archived/Show', [
            'request' => $supplyRequest,
        ]);
    }

    private function archivedRequestQuery(Request $request, string $search): Builder
    {
        return SupplyRequest::query()
            ->archived()
            ->where('archived_by', $request->user()->id)
            ->with($this->requestDisplayRelations())
            ->withCount('items')
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
            ->latest('archived_at')
            ->latest('updated_at');
    }

    private function requestDisplayRelations(): array
    {
        return [
            'department:id,name,type,code',
            'user:id,name,external_department_reference_id',
            'user.externalDepartmentReference:id,name,department_code',
            'hrAdminReleaser:id,name',
            'archiver:id,name',
        ];
    }

    private function requestDetailRelations(): array
    {
        return [
            ...$this->requestDisplayRelations(),
            'approver:id,name',
            'managerApprover:id,name',
            'items.supply',
            'timelines' => fn ($query) => $query
                ->where('action', '!=', 'item_updated')
                ->latest(),
            'timelines.performer:id,name',
        ];
    }
}
