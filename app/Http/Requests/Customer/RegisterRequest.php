<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'phone'     => ['required', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/', 'unique:users,phone'],
            'full_name' => ['required', 'string', 'min:2', 'max:100'],
            'email'     => ['sometimes', 'nullable', 'email', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required'     => 'Vui lòng nhập số điện thoại',
            'phone.regex'        => 'Số điện thoại không hợp lệ',
            'phone.unique'       => 'Số điện thoại đã được đăng ký',
            'full_name.required' => 'Vui lòng nhập họ tên',
            'full_name.min'      => 'Họ tên phải có ít nhất 2 ký tự',
            'email.email'        => 'Email không hợp lệ',
            'email.unique'       => 'Email đã được sử dụng',
            'password.required'  => 'Vui lòng nhập mật khẩu',
            'password.min'       => 'Mật khẩu phải có ít nhất 6 ký tự',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp',
        ];
    }
}
