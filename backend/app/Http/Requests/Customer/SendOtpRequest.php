<?php

namespace App\Http\Requests\Customer;

use App\Rules\VietnamesePhoneRule;
use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'regex:'.VietnamesePhoneRule::PATTERN, 'unique:users,phone'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Vui lòng nhập số điện thoại',
            'phone.regex' => 'Số điện thoại không hợp lệ (10 số, bắt đầu bằng 03/05/07/08/09)',
            'phone.unique' => 'Số điện thoại đã được đăng ký',
        ];
    }
}
