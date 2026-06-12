<?php

namespace App\Http\Requests\Tenant;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTenantUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = (int) $this->route('tenantId');

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,NULL,id,tenant_id,' . $tenantId,
            'role' => ['required', Rule::in(UserRole::tenantRoles())],
            'password' => 'required|string|min:8|confirmed',
        ];
    }
}
