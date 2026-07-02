<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = $this->route('tenant')?->id ?? $this->route('tenant');

        $rules = [
            'name' => 'required|string|max:120',
            'domain' => ['nullable', 'string', 'max:120', Rule::unique('tenants', 'domain')->ignore($tenantId)],
            'primary_color' => 'required|string|max:7',
            'secondary_color' => 'required|string|max:7',
            'locale' => 'required|in:ar,en',
            'timezone' => 'required|string|max:60',
            'currency' => 'required|in:SDG,USD',
            'exchange_rate' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'logo' => 'nullable|image|max:2048',
            'moduleToggles' => 'array',
        ];

        if (! $tenantId) {
            $rules += [
                'branch_name' => 'required|string|max:120',
                'branch_code' => 'required|string|max:10',
                'admin_name' => 'required|string|max:120',
                'admin_email' => ['required', 'email', Rule::unique('users', 'email')],
                'admin_password' => 'required|string|min:8',
            ];
        }

        return $rules;
    }
}
