<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class ReplyMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('customer')->check();
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:2', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'Vui lòng nhập nội dung tin nhắn.',
            'body.min' => 'Nội dung phản hồi phải có ít nhất 2 ký tự.',
        ];
    }
}
