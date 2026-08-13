<?php

namespace App\Http\Requests\Operator;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Lưu bảng giá theo TUYẾN trong một lần: tuyến không có trong payload sẽ bị xoá
 * đơn giá (quay lại trạng thái "chưa có giá").
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
            'rates' => ['present', 'array', 'max:500'],
            'rates.*.route_id' => ['required', 'uuid', 'exists:routes,id'],
            'rates.*.base_fare' => ['required', 'integer', 'min:0', 'max:1000000'],
            'rates.*.price_per_km' => ['required', 'numeric', 'min:0', 'max:100000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $operator = auth('operator')->user()?->operator;
            $ownedIds = $operator ? $operator->routes()->pluck('id')->all() : [];
            $seen = [];

            foreach ((array) $this->input('rates', []) as $i => $rate) {
                $routeId = $rate['route_id'] ?? null;

                // Chặn gán giá cho tuyến của nhà xe khác (exists: không đủ)
                if (! in_array($routeId, $ownedIds, true)) {
                    $validator->errors()->add("rates.{$i}.route_id", 'Tuyến không thuộc nhà xe của bạn');

                    continue;
                }

                if (isset($seen[$routeId])) {
                    $validator->errors()->add("rates.{$i}.route_id", 'Tuyến này đã có dòng giá khác');
                }

                $seen[$routeId] = true;
            }
        });
    }

    public function messages(): array
    {
        return [
            'rates.*.route_id.required' => 'Vui lòng chọn tuyến',
            'rates.*.price_per_km.required' => 'Vui lòng nhập đơn giá mỗi km',
        ];
    }
}
