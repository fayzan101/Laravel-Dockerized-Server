<?php

namespace App\Http\Requests\Usage;

use Illuminate\Foundation\Http\FormRequest;

class ReportUsageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'feature_key' => 'required|string',
            'amount' => 'required|integer|min:1',
            'metadata' => 'nullable|array',
            'recorded_at' => 'nullable|date',
        ];
    }
}
