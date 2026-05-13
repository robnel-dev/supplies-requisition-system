<?php

namespace App\Http\Controllers\Approver;

use App\Http\Controllers\Controller;
use App\Models\SupplyRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ApprovalHistoryController extends Controller
{
    private const HANDLED_ACTIONS = ['approved', 'rejected'];

    public function index(Request $request)
    {
        Gate::authorize('viewAny', SupplyRequest::class);

        $search = trim((string) $request->input('search', ''));

        $requests = $this->historyQuery($request->user(), $search)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Approver/ApprovalHistory/Index', [
            'requests' => $requests,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(Request $request, SupplyRequest $supplyRequest)
    {
        Gate::authorize('viewApprovalHistory', $supplyRequest);

        $handledEvent = $supplyRequest->timelines()
            ->with('performer')
            ->where('performed_by', $request->user()->id)
            ->whereIn('action', self::HANDLED_ACTIONS)
            ->latest()
            ->firstOrFail();

        $supplyRequest->load($this->requestDetailRelations());

        return Inertia::render('Approver/ApprovalHistory/Show', [
            'request' => $supplyRequest,
            'handledEvent' => $handledEvent,
        ]);
    }

    private function historyQuery(User $approver, string $search): Builder
    {
        return SupplyRequest::query()
            ->with($this->requestDisplayRelations())
            ->withCount('items')
            ->withMax([
                'timelines as acted_at' => fn (Builder $query) => $query
                    ->where('performed_by', $approver->id)
                    ->whereIn('action', self::HANDLED_ACTIONS),
            ], 'created_at')
            ->with([
                'timelines' => fn ($query) => $query
                    ->where('performed_by', $approver->id)
                    ->whereIn('action', self::HANDLED_ACTIONS)
                    ->latest(),
            ])
            ->whereHas('timelines', fn (Builder $query) => $query
                ->where('performed_by', $approver->id)
                ->whereIn('action', self::HANDLED_ACTIONS))
            ->whereNotIn('status', [
                SupplyRequest::STATUS_DRAFT,
                SupplyRequest::STATUS_PENDING_APPROVAL,
            ])
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('transaction_id', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                            ->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('acted_at')
            ->latest('request_date');
    }

    private function requestDisplayRelations(): array
    {
        return [
            'department:id,name,type',
            'user:id,name,external_department_reference_id',
            'user.externalDepartmentReference:id,name',
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
            'timelines.performer:id,name',
        ];
    }
}
