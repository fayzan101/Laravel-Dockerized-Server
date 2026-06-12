<?php

namespace App\Http\Requests\Iam;

use Illuminate\Foundation\Http\FormRequest;

class CreatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'name' => 'required|string|max:255|unique:permissions,name,NULL,id,tenant_id,' . $tenantId,
            'description' => 'nullable|string|max:255',
        ];
    }
}
