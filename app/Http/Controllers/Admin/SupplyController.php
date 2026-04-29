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

        // For filters
        $search = $request->input('search');
        $status = $request->input('status');
        $category = $request->input('category');

        $supplies = Supply::with('reference')
            ->when($search, function ($query, $search) {
                // Wrapped in a nested where to prevent 'OR' bleed-over into the status/category filters
                $query->where(function ($q) use ($search) {
                    $q->where('item_code', 'like', "%{$search}%")
                        ->orWhere('item_name', 'like', "%{$search}%") // Search internal custom names
                        ->orWhereHas('reference', function ($refQ) use ($search) {
                            $refQ->where('item_name', 'like', "%{$search}%"); // Search external names
                        });
                });
            })
            // Strict check to ensure empty strings from the "All Statuses" dropdown don't trigger this
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
        // Added Security Gate!
        Gate::authorize('delete', $supply);

        // Important: Depending on Phase 3, you might not want to delete supplies 
        // that have already been tied to an employee request. 
        // If it throws a foreign key error, your Vue frontend will gracefully show an error toast!
        $supply->delete();

        return redirect()->back();
    }

    public function toggleStatus(Supply $supply)
    {
        Gate::authorize('update', $supply);
        $this->supplyService->toggleActiveStatus($supply);

        return redirect()->back()->with('success', 'Status toggled successfully.');
    }

    // Endpoint for Vue Auto-fill feature
    public function searchExternal(Request $request)
    {
        $term = $request->input('term');

        // Added a quick exit if term is empty to save database queries
        if (!$term) {
            return response()->json([]);
        }

        $references = ExternalSupplyReference::where('item_code', 'like', "%{$term}%")
            ->orWhere('item_name', 'like', "%{$term}%")
            ->limit(5) // Keep it light
            ->get();

        return response()->json($references);
    }
}
