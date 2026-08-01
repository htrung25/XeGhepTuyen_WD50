<?php

namespace App\Http\Requests\Customer;

use App\Enums\TicketStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ListSupportTicketsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', new Enum(TicketStatusEnum::class)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
