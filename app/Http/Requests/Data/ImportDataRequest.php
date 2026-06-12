<?php

namespace App\Http\Requests\Data;

use Illuminate\Foundation\Http\FormRequest;

class ImportDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data' => 'required|array',
            'data.users' => 'sometimes|array',
            'data.users.*.name' => 'required_with:data.users|string|max:255',
            'data.users.*.email' => 'required_with:data.users|email|max:255',
            'data.users.*.role' => 'sometimes|string|in:admin,member',
        ];
    }
}
