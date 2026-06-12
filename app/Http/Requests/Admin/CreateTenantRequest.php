<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tenants,slug',
            'description' => 'nullable|string|max:1000',
            'domain' => 'nullable|string|max:255|unique:tenants,domain',
            'owner_email' => 'required|email|unique:users,email',
            'owner_name' => 'required|string|max:255',
            'owner_password' => 'required|string|min:8|confirmed',
        ];
    }
}
