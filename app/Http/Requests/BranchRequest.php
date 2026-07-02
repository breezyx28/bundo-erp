<?php

namespace App\Http\Requests;

use App\Services\Branch\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('branches.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(BranchContext::class)->currentTenantId();

        return [
            'name' => 'required|string|max:120',
            'code' => [
                'required', 'string', 'max:10',
                Rule::unique('branches', 'code')
                    ->where('tenant_id', $tenantId)
                    ->ignore($this->route('branch')),
            ],
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120',
            'primary_color' => 'required|string|max:7',
            'secondary_color' => 'required|string|max:7',
            'is_active' => 'boolean',
        ];
    }
}
