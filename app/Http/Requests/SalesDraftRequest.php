<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalesDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('invoices.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => 'nullable|integer|exists:sales_invoices,id',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'sale_type' => 'nullable|in:cash,credit',
            'invoice_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'exchange_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'hold_label' => 'nullable|string|max:120',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ];
    }
}
