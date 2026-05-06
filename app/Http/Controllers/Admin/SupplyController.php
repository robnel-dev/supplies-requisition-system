<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supply;
use App\Models\ExternalSupplyReference;
use App\Services\SupplyService;
use App\Http\Requests\Admin\StoreSupplyRequest;
use App\Http\Requests\Admin\UpdateSupplyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class SupplyController extends Controller
{
    public function __construct(protected SupplyService $supplyService) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Supply::class);

        $search = $request->input('search');
        $status = $request->input('status');
        $category = $request->input('category');

        // CROSS-DATABASE  
        $matchingExternalCodes = [];
        if ($search) {
            $matchingExternalCodes = ExternalSupplyReference::where('item_code', 'like', "%{$search}%")
                ->orWhere('item_description', 'like', "%{$search}%")
                ->pluck('item_code')
                ->toArray();
        }

        $supplies = Supply::with('reference')
            ->when($search, function ($query) use ($search, $matchingExternalCodes) {
                $query->where(function ($q) use ($search, $matchingExternalCodes) {
                    $q->where('item_code', 'like', "%{$search}%")
                        ->orWhere('item_description', 'like', "%{$search}%");

                    if (!empty($matchingExternalCodes)) {
                        $q->orWhereIn('item_code', $matchingExternalCodes);
                    }
                });
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                $query->where('is_active', $status === 'active');
            })
            ->when($category, function ($query) use ($category) {
                $query->where('category', $category);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Supplies/Index', [
            'supplies' => $supplies,
            'filters' => $request->only(['search', 'status', 'category']),
            'stats' => [
                'total' => Supply::count(),
                'active' => Supply::where('is_active', true)->count(),
            ],
        ]);
    }

    public function store(StoreSupplyRequest $request)
    {
        Gate::authorize('create', Supply::class);
        $this->supplyService->createSupply($request->validated());
        return redirect()->back()->with('success', 'Supply added successfully.');
    }

    public function update(UpdateSupplyRequest $request, Supply $supply)
    {
        Gate::authorize('update', $supply);
        $this->supplyService->updateSupply($supply, $request->validated());
        return redirect()->back()->with('success', 'Supply updated successfully.');
    }

    public function destroy(Supply $supply)
    {
        Gate::authorize('delete', $supply);
        $this->supplyService->deleteSupply($supply);
        return redirect()->back()->with('success', 'Supply deleted.');
    }

    public function toggleStatus(Supply $supply)
    {
        Gate::authorize('update', $supply);
        $this->supplyService->toggleActiveStatus($supply);
        return redirect()->back()->with('success', 'Status toggled successfully.');
    }

    public function searchExternal(Request $request)
    {
        $term = $request->input('term');

        if (!$term) {
            return response()->json([]);
        }

        $references = ExternalSupplyReference::where('item_code', 'like', "%{$term}%")
            ->orWhere('item_description', 'like', "%{$term}%")
            ->limit(5)
            ->get();

        return response()->json($references);
    }
}
