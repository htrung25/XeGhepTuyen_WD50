<?php

namespace App\Http\Requests\Operator;

use App\Services\VietnamAdministrative;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Lưu toàn bộ bảng giá của nhà xe trong một lần (upsert + xoá dòng bị bỏ).
 * Dòng có district_code = null áp cho cả tỉnh; cả hai null = mặc định nhà xe.
 */
class SaveFareRatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('operator')->check();
    }

    public function rules(): array
    {
        return [
            'rates' => ['present', 'array', 'max:200'],
            'rates.*.province_code' => ['nullable', 'string', 'max:10'],
            'rates.*.district_code' => ['nullable', 'string', 'max:10'],
            'rates.*.base_fare' => ['required', 'integer', 'min:0', 'max:1000000'],
            'rates.*.price_per_km' => ['required', 'numeric', 'min:0', 'max:100000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $seen = [];

            foreach ((array) $this->input('rates', []) as $i => $rate) {
                $province = $rate['province_code'] ?? null;
                $district = $rate['district_code'] ?? null;

                if ($province === null && $district !== null) {
                    $validator->errors()->add("rates.{$i}.province_code", 'Chọn huyện thì phải chọn tỉnh');

                    continue;
                }

                if ($province !== null && ! VietnamAdministrative::provinceExists($province)) {
                    $validator->errors()->add("rates.{$i}.province_code", 'Tỉnh/thành không hợp lệ');

                    continue;
                }

                if ($district !== null && ! VietnamAdministrative::districtExists($province, $district)) {
                    $validator->errors()->add("rates.{$i}.district_code", 'Quận/huyện không thuộc tỉnh đã chọn');

                    continue;
                }

                // Unique index của MySQL không chặn được trùng khi có NULL,
                // nên phải tự kiểm tra ở đây.
                $key = ($province ?? '-').'|'.($district ?? '-');

                if (isset($seen[$key])) {
                    $validator->errors()->add("rates.{$i}.district_code", 'Khu vực này đã có dòng giá khác');
                }

                $seen[$key] = true;
            }
        });
    }
}
