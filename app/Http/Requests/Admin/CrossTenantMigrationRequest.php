<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CrossTenantMigrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_tenant_id' => 'required|integer|exists:tenants,id',
            'target_tenant_id' => 'required|integer|exists:tenants,id|different:source_tenant_id',
        ];
    }
}
