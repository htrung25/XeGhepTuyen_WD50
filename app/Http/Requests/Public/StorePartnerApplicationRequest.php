<?php

namespace App\Http\Requests\Public;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StorePartnerApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public — bất kỳ ai cũng có thể gửi đơn đăng ký
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'min:2', 'max:200'],
            'tax_code' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:500'],
            // Cơ cấu đội xe theo loại — mỗi loại là số nguyên ≥ 0; tổng ≥ 1 (kiểm ở withValidator)
            'fleet_breakdown' => ['required', 'array'],
            'fleet_breakdown.sedan_4' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'fleet_breakdown.mpv_7' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'fleet_breakdown.van_9' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'fleet_breakdown.minibus_16' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'representative_name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['required', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/'],
            'email' => ['nullable', 'email', 'max:100'],
            'business_license' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'fleet_images' => ['nullable', 'array', 'max:5'],
            'fleet_images.*' => ['image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $total = collect($this->input('fleet_breakdown', []))
                ->only(['sedan_4', 'mpv_7', 'van_9', 'minibus_16'])
                ->sum(fn ($v) => (int) $v);

            if ($total < 1) {
                $validator->errors()->add('fleet_breakdown', 'Vui lòng khai báo ít nhất 1 xe trong đội xe');
            }
        });
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'Vui lòng nhập tên nhà xe / công ty',
            'company_name.min' => 'Tên nhà xe phải có ít nhất 2 ký tự',
            'representative_name.min' => 'Họ tên người đại diện phải có ít nhất 2 ký tự',
            'tax_code.required' => 'Vui lòng nhập mã số thuế',
            'address.required' => 'Vui lòng nhập địa chỉ trụ sở',
            'fleet_breakdown.required' => 'Vui lòng khai báo cơ cấu đội xe',
            'fleet_breakdown.*.integer' => 'Số lượng xe phải là số nguyên',
            'fleet_breakdown.*.min' => 'Số lượng xe không hợp lệ',
            'representative_name.required' => 'Vui lòng nhập họ tên người đại diện',
            'phone.required' => 'Vui lòng nhập số điện thoại',
            'phone.regex' => 'Số điện thoại không hợp lệ',
            'email.email' => 'Email không hợp lệ',
            'business_license.mimes' => 'Giấy phép kinh doanh phải là PDF, JPG hoặc PNG',
            'business_license.max' => 'Giấy phép kinh doanh tối đa 10MB',
            'fleet_images.max' => 'Tối đa 5 ảnh đội xe',
            'fleet_images.*.image' => 'Hồ sơ đính kèm phải là hình ảnh',
            'fleet_images.*.max' => 'Mỗi ảnh tối đa 10MB',
        ];
    }
}
