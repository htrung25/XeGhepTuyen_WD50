<?php

namespace App\Http\Requests\Admin;

use App\Enums\TicketCategoryEnum;
use App\Enums\TicketPriorityEnum;
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
            'category' => ['nullable', new Enum(TicketCategoryEnum::class)],
            'priority' => ['nullable', new Enum(TicketPriorityEnum::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
