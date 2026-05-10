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
        $query = Supply::with('reference')->where('is_active', true);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('item_code', 'like', "%{$search}%")
                    ->orWhere('item_description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category') && $request->category !== 'All Categories') {
            $query->where('category', $request->category);
        }

        $supplies = $query->latest()->paginate(10)->withQueryString();

        $categories = Supply::where('is_active', true)
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->filter()
            ->values();

        // The catalog doubles as the draft editor, so it receives the current draft cart.
        $draft = $cartService->getActiveDraft($request->user());

        return Inertia::render('Requestor/Catalog/Index', [
            'supplies'   => $supplies,
            'filters'    => $request->only(['search', 'category']),
            'categories' => $categories,
            'cart'       => $draft?->load('items'),
            'editingTransactionId'  => $draft?->transaction_id,
        ]);
    }
}
