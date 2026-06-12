<?php

namespace App\Http\Requests\Feature;

use Illuminate\Foundation\Http\FormRequest;

class OverrideFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'feature_key' => 'required|string|exists:features,key',
            'enabled' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:0',
        ];
    }
}
