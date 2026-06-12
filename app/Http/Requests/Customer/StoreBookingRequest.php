<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('customer')->check();
    }

    public function rules(): array
    {
        return [
            'trip_id'               => ['required', 'uuid', 'exists:trips,id'],
            'seat_ids'              => ['required', 'array', 'min:1', 'max:4'],
            'seat_ids.*'            => ['uuid', 'exists:seat_maps,id'],
            'pickup_stop_id'        => ['required', 'uuid', 'exists:route_stops,id'],
            'dropoff_stop_id'       => ['required', 'uuid', 'exists:route_stops,id'],
            'pickup_address'        => ['nullable', 'string', 'max:500'],
            'dropoff_address'       => ['nullable', 'string', 'max:500'],
            'passenger_count'       => ['required', 'integer', 'min:1', 'max:4'],
            'contact_name'          => ['required', 'string', 'min:2', 'max:100'],
            'contact_phone'         => ['required', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/'],
            'note'                  => ['nullable', 'string', 'max:500'],
            'payment_method'        => ['required', 'in:momo,vnpay,zalopay,wallet,cash'],
            'voucher_code'          => ['nullable', 'string', 'max:20'],
            'passengers'            => ['required', 'array', 'min:1', 'max:4'],
            'passengers.*.full_name'=> ['required', 'string', 'min:2', 'max:100'],
            'passengers.*.phone'    => ['nullable', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'trip_id.required'               => 'Vui lòng chọn chuyến đi',
            'trip_id.exists'                 => 'Chuyến đi không tồn tại',
            'seat_ids.required'              => 'Vui lòng chọn ghế',
            'seat_ids.max'                   => 'Tối đa 4 ghế mỗi lần đặt',
            'pickup_stop_id.required'        => 'Vui lòng chọn điểm đón',
            'dropoff_stop_id.required'       => 'Vui lòng chọn điểm trả',
            'passenger_count.required'       => 'Vui lòng nhập số hành khách',
            'passenger_count.max'            => 'Tối đa 4 hành khách mỗi lần đặt',
            'contact_name.required'          => 'Vui lòng nhập tên người liên hệ',
            'contact_phone.required'         => 'Vui lòng nhập số điện thoại liên hệ',
            'contact_phone.regex'            => 'Số điện thoại không hợp lệ',
            'payment_method.required'        => 'Vui lòng chọn phương thức thanh toán',
            'payment_method.in'              => 'Phương thức thanh toán không hợp lệ',
            'passengers.required'            => 'Vui lòng nhập thông tin hành khách',
            'passengers.*.full_name.required'=> 'Vui lòng nhập tên hành khách',
        ];
    }
}
