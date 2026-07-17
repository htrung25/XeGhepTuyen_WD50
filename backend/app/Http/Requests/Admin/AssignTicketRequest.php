<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'assigned_to' => [
                'required',
                'uuid',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', UserRoleEnum::Admin->value);
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_to.required' => 'Vui lòng chọn nhân viên được giao việc.',
            'assigned_to.exists' => 'Nhân viên được chọn không hợp lệ hoặc không có quyền quản trị.',
        ];
    }
}
