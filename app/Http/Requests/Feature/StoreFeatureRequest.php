<?php

namespace App\Http\Requests\Feature;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => 'required|string|max:100|unique:features,key',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'default_enabled' => 'boolean',
            'default_limit' => 'nullable|integer|min:0',
        ];
    }
}
