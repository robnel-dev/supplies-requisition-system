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
            'supply_id' => ['required', 'integer', 'exists:supplies,id'],
            'quantity'  => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        $this->cartService->addItem(
            $request->user(),
            (int) $request->supply_id,
            (int) $request->quantity
        );

        return back()->with('success', 'Item added to your request list draft.');
    }

    public function update(Request $request, $itemId)
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        $this->cartService->updateItemQuantity(
            $request->user(),
            (int) $itemId,
            (int) $request->quantity
        );

        return back();
    }

    public function destroy(Request $request, $itemId)
    {
        $this->cartService->removeItem($request->user(), (int) $itemId);
        return back()->with('success', 'Item removed from your request list.');
    }

    public function checkout(Request $request)
    {
        try {
            $transactionId = $this->cartService->checkout($request->user());

            return redirect()
                ->route('requestor.requests.index')
                ->with('success', "Request #{$transactionId} submitted and is now pending approval.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Exception $e) {
            return back()->withErrors([
                'checkout' => $e->getMessage()
            ]);
        }
    }
}
