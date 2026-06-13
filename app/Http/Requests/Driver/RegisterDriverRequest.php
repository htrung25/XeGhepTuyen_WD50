<?php

namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDriverRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'phone'          => ['required', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/', 'unique:users,phone'],
            'full_name'      => ['required', 'string', 'min:2', 'max:100'],
            'password'       => ['required', 'string', 'min:6', 'confirmed'],
            'operator_id'    => ['required', 'uuid', 'exists:operators,id'],
            'license_number' => ['required', 'string', 'max:20', 'unique:drivers,license_number'],
            'license_class'  => ['required', 'in:B2,C,D,E'],
            'license_expiry' => ['required', 'date', 'after:today'],
            'id_card_number' => ['required', 'string', 'max:20'],
            'id_card_front'  => ['required', 'image', 'max:5120'],
            'id_card_back'   => ['required', 'image', 'max:5120'],
            'license_front'  => ['required', 'image', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique'           => 'Số điện thoại đã được đăng ký',
            'phone.regex'            => 'Số điện thoại không hợp lệ',
            'operator_id.exists'     => 'Nhà xe không tồn tại',
            'license_number.unique'  => 'Số GPLX đã được đăng ký',
            'license_class.in'       => 'Hạng GPLX không hợp lệ (B2/C/D/E)',
            'license_expiry.after'   => 'GPLX đã hết hạn',
            'id_card_front.required' => 'Vui lòng tải ảnh CMND mặt trước',
            'id_card_back.required'  => 'Vui lòng tải ảnh CMND mặt sau',
            'license_front.required' => 'Vui lòng tải ảnh GPLX mặt trước',
            'id_card_front.max'      => 'Ảnh tối đa 5MB',
        ];
    }
}
