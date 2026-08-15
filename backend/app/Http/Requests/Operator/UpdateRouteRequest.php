<?php

namespace App\Http\Requests\Operator;

use App\Services\VietnamAdministrative;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('operator')->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:200'],
            'origin_province_code' => ['sometimes', 'string', 'max:10'],
            'origin_district_code' => ['sometimes', 'string', 'max:10'],
            'dest_province_code' => ['sometimes', 'string', 'max:10'],
            'dest_district_code' => ['sometimes', 'string', 'max:10'],
            'distance_km' => ['sometimes', 'integer', 'min:1', 'max:2000'],
            'est_duration_min' => ['sometimes', 'integer', 'min:1', 'max:1440'],
            'is_round_trip' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Tỉnh và huyện phải đi thành cặp — sửa mỗi tỉnh mà giữ huyện cũ sẽ
            // tạo ra cặp không tồn tại (VD: Hải Phòng + Quận Ba Đình).
            foreach ([['origin_province_code', 'origin_district_code', 'điểm đi'],
                ['dest_province_code', 'dest_district_code', 'điểm đến']] as [$pKey, $dKey, $label]) {
                if (! $this->has($pKey) && ! $this->has($dKey)) {
                    continue;
                }

                if (! $this->has($pKey) || ! $this->has($dKey)) {
                    $validator->errors()->add($dKey, "Phải chọn cả tỉnh và huyện cho {$label}");

                    continue;
                }

                if (! VietnamAdministrative::provinceExists($this->input($pKey))) {
                    $validator->errors()->add($pKey, "Tỉnh/thành {$label} không hợp lệ");
                } elseif (! VietnamAdministrative::districtExists($this->input($pKey), $this->input($dKey))) {
                    $validator->errors()->add($dKey, "Quận/huyện {$label} không thuộc tỉnh đã chọn");
                }
            }
        });
    }
}
