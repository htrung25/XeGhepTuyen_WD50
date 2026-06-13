<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'phone'    => ['required', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required'    => 'Vui lòng nhập số điện thoại',
            'phone.regex'       => 'Số điện thoại không hợp lệ',
            'password.required' => 'Vui lòng nhập mật khẩu',
        ];
    }
}
