<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;

class IndexOperatorHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('operator')->check();
    }

    public function rules(): array
    {
        return [
            'category' => ['nullable', 'in:booking,trip,vehicle,driver'],
            'severity' => ['nullable', 'in:info,success,warning,danger'],
            'action' => ['nullable', 'string', 'max:80'],
            'search' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
