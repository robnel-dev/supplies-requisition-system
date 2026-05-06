<?php

namespace App\Http\Controllers\Requestor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CartService;

class CartController extends Controller
{
    public function __construct(private CartService $cartService) {}

    public function store(Request $request)
    {
        $request->validate([
            'supply_id' => ['required', 'exists:supplies,id'],
            'quantity' => ['required', 'integer', 'min:1']
        ]);

        $this->cartService->addItem($request->user(), $request->supply_id, $request->quantity);

        return back()->with('success', 'Item added to cart.');
    }

    public function update(Request $request, $itemId)
    {
        $request->validate(['quantity' => ['required', 'integer', 'min:1']]);
        $this->cartService->updateItemQuantity($request->user(), $itemId, $request->quantity);
        return back();
    }

    public function destroy(Request $request, $itemId)
    {
        $this->cartService->removeItem($request->user(), $itemId);
        return back()->with('success', 'Item removed.');
    }

    public function checkout(Request $request)
    {
        try {
            $this->cartService->checkout($request->user());
            // Redirect to a success/history page. For now, back with success message.
            return back()->with('success', 'Request submitted successfully and is pending approval.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
