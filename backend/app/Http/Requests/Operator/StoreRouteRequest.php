<?php

namespace App\Http\Requests\Operator;

use App\Services\VietnamAdministrative;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Nhà xe chọn TỈNH + HUYỆN cho điểm đi/điểm đến (không nhập text tự do), không
 * nhập giá vé (BE tính theo bảng giá km) và không khai điểm dừng nữa.
 */
class StoreRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('operator')->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'origin_province_code' => ['required', 'string', 'max:10'],
            'origin_district_code' => ['required', 'string', 'max:10'],
            'dest_province_code' => ['required', 'string', 'max:10'],
            'dest_district_code' => ['required', 'string', 'max:10'],
            'distance_km' => ['required', 'integer', 'min:1', 'max:2000'],
            'est_duration_min' => ['required', 'integer', 'min:1', 'max:1440'],
            'is_round_trip' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $originProvince = $this->input('origin_province_code');
            $destProvince = $this->input('dest_province_code');

            if (! VietnamAdministrative::provinceExists($originProvince)) {
                $validator->errors()->add('origin_province_code', 'Tỉnh/thành điểm đi không hợp lệ');
            } elseif (! VietnamAdministrative::districtExists($originProvince, $this->input('origin_district_code'))) {
                $validator->errors()->add('origin_district_code', 'Quận/huyện điểm đi không thuộc tỉnh đã chọn');
            }

            if (! VietnamAdministrative::provinceExists($destProvince)) {
                $validator->errors()->add('dest_province_code', 'Tỉnh/thành điểm đến không hợp lệ');
            } elseif (! VietnamAdministrative::districtExists($destProvince, $this->input('dest_district_code'))) {
                $validator->errors()->add('dest_district_code', 'Quận/huyện điểm đến không thuộc tỉnh đã chọn');
            }

            if ($originProvince === $destProvince
                && $this->input('origin_district_code') === $this->input('dest_district_code')) {
                $validator->errors()->add('dest_district_code', 'Điểm đến phải khác điểm đi');
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên tuyến đường',
            'origin_province_code.required' => 'Vui lòng chọn tỉnh/thành điểm đi',
            'origin_district_code.required' => 'Vui lòng chọn quận/huyện điểm đi',
            'dest_province_code.required' => 'Vui lòng chọn tỉnh/thành điểm đến',
            'dest_district_code.required' => 'Vui lòng chọn quận/huyện điểm đến',
            'distance_km.required' => 'Vui lòng nhập khoảng cách (km)',
        ];
    }
}
