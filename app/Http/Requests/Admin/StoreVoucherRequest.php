<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'code'           => ['required', 'string', 'max:20', 'unique:vouchers,code', 'regex:/^[A-Z0-9]+$/'],
            'operator_id'    => ['nullable', 'uuid', 'exists:operators,id'],
            'discount_type'  => ['required', 'in:percent,fixed'],
            'discount_value' => ['required', 'numeric', 'min:1'],
            'min_order'      => ['nullable', 'integer', 'min:0'],
            'max_discount'   => ['nullable', 'integer', 'min:0'],
            'usage_limit'    => ['required', 'integer', 'min:1'],
            'valid_from'     => ['required', 'date'],
            'valid_until'    => ['required', 'date', 'after:valid_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'          => 'Vui lòng nhập mã voucher',
            'code.unique'            => 'Mã voucher đã tồn tại',
            'code.regex'             => 'Mã voucher chỉ gồm chữ hoa và số',
            'discount_type.required' => 'Vui lòng chọn loại giảm giá',
            'discount_type.in'       => 'Loại giảm giá không hợp lệ',
            'discount_value.required'=> 'Vui lòng nhập giá trị giảm',
            'usage_limit.required'   => 'Vui lòng nhập số lần sử dụng',
            'valid_from.required'    => 'Vui lòng nhập ngày bắt đầu',
            'valid_until.required'   => 'Vui lòng nhập ngày kết thúc',
            'valid_until.after'      => 'Ngày kết thúc phải sau ngày bắt đầu',
        ];
    }
}
