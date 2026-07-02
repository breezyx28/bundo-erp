<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->route('product') ? 'products.update' : 'products.create';

        return $this->user()?->can($permission) ?? false;
    }

    /**
     * Auto-generate a SKU when none is supplied (mirrors the old save() logic).
     */
    protected function prepareForValidation(): void
    {
        if (! $this->input('sku')) {
            $this->merge(['sku' => 'SKU-'.Str::upper(Str::random(8))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit' => 'required|string|max:50',
            'cost_price' => 'numeric|min:0',
            'selling_price' => 'numeric|min:0',
            'reorder_level' => 'integer|min:0',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'has_variants' => 'boolean',
            'image' => 'nullable|image|max:4096',
            'variants' => 'array',
            'variants.*.sku' => 'required_with:variants|string|max:100',
            'variants.*.cost_price' => 'numeric|min:0',
            'variants.*.selling_price' => 'numeric|min:0',
        ];
    }
}
