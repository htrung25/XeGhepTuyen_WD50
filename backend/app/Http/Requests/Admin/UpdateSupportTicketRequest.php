<?php

namespace App\Http\Requests\Admin;

use App\Enums\TicketPriorityEnum;
use App\Enums\TicketStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'status' => ['required_without:priority', new Enum(TicketStatusEnum::class)],
            'priority' => ['required_without:status', new Enum(TicketPriorityEnum::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Vui lòng chọn trạng thái.',
            'priority.required' => 'Vui lòng chọn mức độ ưu tiên.',
        ];
    }
}
