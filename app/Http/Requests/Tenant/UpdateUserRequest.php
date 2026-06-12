<?php

namespace App\Http\Requests\Tenant;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = (int) $this->route('userId');
        $tenantId = $this->user()->tenant_id;

        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $userId . ',id,tenant_id,' . $tenantId,
            'role' => ['sometimes', Rule::in(UserRole::tenantRoles())],
            'password' => 'sometimes|string|min:8|confirmed',
        ];
    }
}
