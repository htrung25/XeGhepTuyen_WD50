<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;

class StoreRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('operator')->check();
    }

    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:200'],
            'origin_city'     => ['required', 'string', 'max:100'],
            'dest_city'       => ['required', 'string', 'max:100'],
            'distance_km'     => ['required', 'integer', 'min:1', 'max:2000'],
            'est_duration_min'=> ['required', 'integer', 'min:1', 'max:1440'],
            'base_price'      => ['required', 'integer', 'min:50000', 'max:500000'],
            'is_round_trip'   => ['boolean'],
            'stops'           => ['required', 'array', 'min:2'],
            'stops.*.stop_name'     => ['required', 'string', 'max:100'],
            'stops.*.address'       => ['required', 'string', 'max:300'],
            'stops.*.lat'           => ['required', 'numeric', 'between:8,24'],
            'stops.*.lng'           => ['required', 'numeric', 'between:102,110'],
            'stops.*.stop_order'    => ['required', 'integer', 'min:1'],
            'stops.*.offset_minutes'=> ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'Vui lòng nhập tên tuyến đường',
            'base_price.required'    => 'Vui lòng nhập giá vé cơ bản',
            'base_price.min'         => 'Giá vé tối thiểu 50.000đ',
            'base_price.max'         => 'Giá vé tối đa 500.000đ',
            'stops.required'         => 'Tuyến phải có ít nhất 2 điểm dừng',
            'stops.min'              => 'Tuyến phải có ít nhất 2 điểm dừng',
            'stops.*.stop_name.required' => 'Vui lòng nhập tên điểm dừng',
            'stops.*.lat.between'    => 'Vĩ độ không hợp lệ',
            'stops.*.lng.between'    => 'Kinh độ không hợp lệ',
        ];
    }
}
