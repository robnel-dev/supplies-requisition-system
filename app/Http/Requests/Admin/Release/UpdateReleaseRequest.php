<?php

namespace App\Http\Requests\Admin\Release;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReleaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->map(function ($item) {
                $budgetType = trim((string) ($item['budget_type'] ?? ''));

                $item['budget_type'] = $budgetType === '' ? null : $budgetType;
                $item['allocated_quantity'] = ($item['allocated_quantity'] ?? null) === ''
                    ? null
                    : ($item['allocated_quantity'] ?? null);

                return $item;
            })
            ->all();

        $this->merge([
            'm3_ro_number' => trim((string) $this->input('m3_ro_number')),
            'm3_dr_number' => trim((string) $this->input('m3_dr_number')),
            'items' => $items,
        ]);
    }

    public function rules(): array
    {
        return [
            'm3_ro_number' => ['nullable', 'string', 'max:100'],
            'm3_dr_number' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'distinct', Rule::exists('supply_request_items', 'id')],
            'items.*.budget_type' => ['nullable', Rule::in(['budgeted', 'unbudgeted'])],
            'items.*.allocated_quantity' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
