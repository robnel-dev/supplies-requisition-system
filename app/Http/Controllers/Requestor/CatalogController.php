<?php

namespace App\Http\Controllers\Requestor;

use App\Http\Controllers\Controller;
use App\Models\Supply;
use App\Services\CartService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CatalogController extends Controller
{
    public function index(Request $request, CartService $cartService)
    {
        // Only fetch ACTIVE supplies for the catalog
        $query = Supply::with('reference')->where('is_active', true);

        // 1. Search Filter
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('item_description', 'like', '%' . $request->search . '%')
                    ->orWhere('item_code', 'like', '%' . $request->search . '%');
            });
        }

        // 2. Category Filter
        if ($request->filled('category') && $request->category !== 'All Categories') {
            $query->where('category', $request->category);
        }

        $supplies = $query->paginate(10)->withQueryString();

        // 3. Get distinct categories dynamically for the dropdown
        $categories = Supply::where('is_active', true)
            ->select('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();

        // 4. Fetch the user's active draft cart
        $draft = $cartService->getOrCreateDraft($request->user());

        return Inertia::render('Requestor/Catalog/Index', [
            'supplies' => $supplies,
            'filters' => $request->only(['search', 'category']),
            'categories' => $categories,
            'cart' => $draft
        ]);
    }
}
