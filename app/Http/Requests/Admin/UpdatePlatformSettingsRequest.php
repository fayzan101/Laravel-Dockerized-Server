<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings' => 'required|array',
            'settings.maintenance_mode' => 'sometimes|boolean',
            'settings.default_tenant_status' => 'sometimes|in:active,inactive',
            'settings.max_users_per_tenant' => 'sometimes|integer|min:1',
            'settings.support_email' => 'sometimes|email',
        ];
    }
}
