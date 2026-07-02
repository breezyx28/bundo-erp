<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('user');

        $rules = [
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => 'nullable|string|max:30',
            'role' => ['required', Rule::in(Role::query()->whereNotIn('name', ['super_admin'])->pluck('name')->all())],
            'branchIds' => 'required|array|min:1',
            'branchIds.*' => 'integer|exists:branches,id',
            'is_active' => 'boolean',
            'password' => ($userId ? 'nullable' : 'required').'|string|min:8|confirmed',
        ];

        return $rules;
    }
}
