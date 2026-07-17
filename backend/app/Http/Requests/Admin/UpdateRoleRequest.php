<?php

namespace App\Http\Requests\Admin;

use App\Enums\AdminPermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', Rule::in(AdminPermissionEnum::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'name.min' => 'Tên vai trò tối thiểu 2 ký tự',
            'permissions.*.in' => 'Có quyền không hợp lệ trong danh sách',
        ];
    }
}
