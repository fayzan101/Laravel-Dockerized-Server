<?php

namespace App\Http\Requests\Feature;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $featureId = (int) $this->route('featureId');

        return [
            'key' => ['sometimes', 'string', 'max:100', Rule::unique('features', 'key')->ignore($featureId)],
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'default_enabled' => 'boolean',
            'default_limit' => 'nullable|integer|min:0',
        ];
    }
}
