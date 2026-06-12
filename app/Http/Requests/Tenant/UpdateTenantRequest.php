<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->user()->tenant;

        return [
            'name' => 'string|max:255',
            'slug' => 'string|max:255|unique:tenants,slug,' . $tenant->id,
            'description' => 'nullable|string',
            'settings' => 'nullable|json',
        ];
    }
}
