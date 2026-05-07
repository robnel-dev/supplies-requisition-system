<?php

namespace App\Services;

use App\Models\SupplyRequest;
use App\Models\SupplyRequestItem;
use App\Models\Supply;
use Illuminate\Support\Facades\DB;

class CartService
{
    // =========================================================================
    // ROOT CAUSE ANALYSIS OF BOTH BUGS
    // =========================================================================
    //
    // BUG 1: "You have an unsubmitted request in progress. Please submit or
    //         clear your current draft before editing this request."
    //
    // WHAT HAPPENS:
    //   reopenForEdit() calls getActiveDraft() to check if the user already has
    //   a draft. The user clicks "Edit" on request #A (status: pending_approval).
    //   getActiveDraft() runs: WHERE user_id = X AND status = 'draft'
    //   BUT: request #A WAS just reverted to 'draft' (by a previous partial
    //   reopen), OR the user has a real NEW draft they forgot about.
    //   The check `$existingDraft->id !== $supplyRequest->id` is checking the
    //   right thing — but the timing is wrong. The check happens BEFORE the
    //   status is changed, so if the request IS already a draft (e.g. from a
    //   failed previous reopen or a page refresh mid-edit), getActiveDraft()
    //   finds it and treats it as "a different draft", blocking the edit.
    //
    //   Also: checkout() calls getActiveDraft() which is NOT inside the
    //   transaction lock scope. The draft is fetched, then locked — this window
    //   allows the draft to change status between fetch and lock.
    //
    // BUG 2: "Could not submit the request. Please try again."
    //
    // WHAT HAPPENS (scenario A — the most common one):
    //   The user edits their request (reopen → modify → resubmit).
    //   checkout() calls `getActiveDraft()` which returns the reopened draft.
    //   It then tries to update status to 'pending_approval' and generate
    //   a transaction_id. BUT: the transaction_id column has a UNIQUE constraint.
    //   The reopened request ALREADY has a transaction_id from its first
    //   submission. The code does NOT clear the old transaction_id before
    //   regenerating, so the UPDATE fails with a unique constraint violation
    //   which is caught by the generic `catch (\Exception $e)` and shown as
    //   "Could not submit the request. Please try again."
    //
    // WHAT HAPPENS (scenario B):
    //   The JOIN query for counting transactions uses `supply_requests.created_at`
    //   for the year filter. But when a request is reopened and resubmitted,
    //   created_at is the ORIGINAL creation date. If this is close to year-end,
    //   the count may include/exclude records incorrectly, leading to a duplicate
    //   transaction_id being generated.
    //
    // THE FIXES:
    //   Fix 1: In reopenForEdit(), check if the request IS already the draft
    //           (idempotent check) to handle page refresh mid-edit gracefully.
    //   Fix 2: In checkout(), CLEAR the old transaction_id before regenerating.
    //           Also handle the "resubmit" case where transaction_id already
    //           exists — generate a new one safely.
    //   Fix 3: Use request_date (not created_at) for the year count, since
    //           request_date is set/reset on each submission.
    //   Fix 4: Move the draft fetch INSIDE the transaction to close the race window.
    // =========================================================================

    /**
     * Find the user's active draft. Returns null if none exists.
     */
    public function getActiveDraft($user): ?SupplyRequest
    {
        return SupplyRequest::with('items.supply')
            ->where('user_id', $user->id)
            ->where('status', SupplyRequest::STATUS_DRAFT)
            ->first();
    }

    /**
     * Get the active draft or create a new one.
     * Private — only used internally by addItem().
     */
    private function getOrCreateDraft($user): SupplyRequest
    {
        $draft = $this->getActiveDraft($user);

        if (! $draft) {
            $draft = SupplyRequest::create([
                'user_id'       => $user->id,
                'department_id' => $user->department_id,
                'status'        => SupplyRequest::STATUS_DRAFT,
            ]);
        }

        return $draft;
    }

    /**
     * Add an item to the draft. Creates a draft if none exists.
     */
    public function addItem($user, int $supplyId, int $quantity): void
    {
        $draft  = $this->getOrCreateDraft($user);
        $supply = Supply::where('id', $supplyId)
            ->where('is_active', true)
            ->firstOrFail();

        $item = SupplyRequestItem::where('supply_request_id', $draft->id)
            ->where('supply_id', $supply->id)
            ->first();

        if ($item) {
            $item->increment('quantity', $quantity);
        } else {
            $draft->items()->create([
                'supply_id'        => $supply->id,
                'item_code'        => $supply->item_code,
                'item_description' => $supply->item_description ?? '',
                'item_unit'        => $supply->unit,
                'quantity'         => $quantity,
            ]);
        }
    }

    /**
     * Update item quantity inside the draft (ownership-safe).
     */
    public function updateItemQuantity($user, int $itemId, int $quantity): void
    {
        $draft = $this->getActiveDraft($user);
        abort_unless($draft, 404);

        $draft->items()->where('id', $itemId)->update(['quantity' => $quantity]);
    }

    /**
     * Remove an item from the draft (ownership-safe).
     */
    public function removeItem($user, int $itemId): void
    {
        $draft = $this->getActiveDraft($user);
        abort_unless($draft, 404);

        $draft->items()->where('id', $itemId)->delete();
    }

    /**
     * Submit the draft for approval.
     *
     * FIX 2A: The draft is fetched WITH lockForUpdate INSIDE the transaction.
     *          This closes the race window where the draft could change status
     *          between the fetch and the update.
     *
     * FIX 2B: We NULL OUT the old transaction_id BEFORE generating a new one.
     *          This prevents the UNIQUE constraint violation on resubmission,
     *          which was the most common cause of "Could not submit the request."
     *
     * FIX 2C: The year for the series count uses YEAR(request_date) on already-
     *          submitted records. For fresh submissions, request_date is not yet
     *          set, so we use now()->format('Y') as the target year. We count
     *          WHERE request_date IS NOT NULL AND YEAR(request_date) = $year,
     *          which is accurate even for resubmitted requests.
     */
    public function checkout($user): string
    {
        return DB::transaction(function () use ($user) {

            // FIX 2A: Fetch AND lock the draft inside the transaction.
            $draft = SupplyRequest::where('user_id', $user->id)
                ->where('status', SupplyRequest::STATUS_DRAFT)
                ->lockForUpdate()
                ->first();

            if (! $draft) {
                throw new \Exception('No active draft found to submit.');
            }

            if ($draft->items()->count() === 0) {
                throw new \Exception('Cannot submit an empty request.');
            }

            $year       = now()->format('Y');
            $costCenter = $user->cost_center;

            // FIX 2B: NULL the old transaction_id first so the UNIQUE constraint
            // does not block us when generating the new one for resubmissions.
            $draft->update(['transaction_id' => null]);

            // FIX 2C: Count using request_date (the submission date), not created_at.
            // This gives an accurate per-cost-center series number per calendar year,
            // even if the request was originally created in a previous year.
            $count = SupplyRequest::join('users', 'supply_requests.user_id', '=', 'users.id')
                ->where('users.cost_center', $costCenter)
                ->whereNotNull('supply_requests.transaction_id')
                ->whereYear('supply_requests.request_date', $year)
                ->lockForUpdate()
                ->count();

            $series        = $count + 1;
            $transactionId = sprintf('%s-%s-%06d', $costCenter, $year, $series);

            // Snapshot the currently requested quantity at submission time.
            $draft->items()->update(['original_quantity' => DB::raw('quantity')]);

            $draft->update([
                'status'         => SupplyRequest::STATUS_PENDING_APPROVAL,
                'transaction_id' => $transactionId,
                'request_date'   => now(),
            ]);

            $draft->timelines()->create([
                'action'       => 'submitted',
                'description'  => 'Request submitted and is now pending approval.',
                'performed_by' => $user->id,
            ]);

            return $transactionId;
        });
    }

    public function reopenForEdit($user, SupplyRequest $supplyRequest): void
    {
        DB::transaction(function () use ($user, $supplyRequest) {

            // Lock the specific request row to prevent concurrent edits
            $locked = SupplyRequest::where('id', $supplyRequest->id)
                ->lockForUpdate()
                ->first();

            // if it's already a draft, nothing to do.
            // This handles: double-click Edit, or page refresh after a partial reopen.
            if ($locked->status === SupplyRequest::STATUS_DRAFT) {
                return; // Already a draft — silently succeed.
            }

            // Only pending_approval can be reopened
            if ($locked->status !== SupplyRequest::STATUS_PENDING_APPROVAL) {
                throw new \Exception('Only requests pending approval can be edited.');
            }

            // Check for a DIFFERENT draft. Exclude this very request.
            $existingDraft = SupplyRequest::where('user_id', $user->id)
                ->where('status', SupplyRequest::STATUS_DRAFT)
                ->where('id', '!=', $supplyRequest->id)
                ->lockForUpdate()
                ->first();

            if ($existingDraft) {
                throw new \Exception(
                    'You have another unsubmitted request in progress. ' .
                        'Please submit or clear your current draft before editing this request.'
                );
            }

            // Clear transaction_id and request_date on reopen so they
            // are freshly generated when the user resubmits.
            $locked->update([
                'status'         => SupplyRequest::STATUS_DRAFT,
                'transaction_id' => null,   // Will be regenerated on next checkout
                'request_date'   => null,   // Will be set fresh on next checkout
            ]);

            $locked->timelines()->create([
                'action'       => 'reopened',
                'description'  => 'Request reopened for editing by the requestor.',
                'performed_by' => $user->id,
            ]);
        });
    }
}
