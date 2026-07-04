<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Services\Branch\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('categories.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(BranchContext::class)->currentTenantId();
        /** @var Category|null $category */
        $category = $this->route('category');

        return [
            'name' => 'required|string|max:255',
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('tenant_id', $tenantId),
                Rule::notIn($category ? [$category->id] : []),
            ],
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $parentId = $this->input('parent_id');

            if (! $parentId) {
                return;
            }

            /** @var Category|null $category */
            $category = $this->route('category');

            $parent = Category::query()->find($parentId);

            if ($parent && $parent->parent_id !== null) {
                $validator->errors()->add('parent_id', __('fields.parent_must_be_root'));
            }

            if ($category && $category->children()->exists()) {
                $validator->errors()->add('parent_id', __('fields.parent_has_children'));
            }
        });
    }
}
