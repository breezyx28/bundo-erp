<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->route('expense') ? 'expenses.update' : 'expenses.create';

        return $this->user()?->can($permission) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'expense_category_id' => 'required|integer|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:1000',
            'expense_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,check',
            'receipt_number' => 'nullable|string|max:100',
            'receipt' => 'nullable|image|max:4096',
            'linked' => 'boolean',
            'purchase_order_id' => 'nullable|required_if:linked,true|integer|exists:purchase_orders,id',
        ];
    }
}
