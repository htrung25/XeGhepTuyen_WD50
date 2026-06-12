<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('customer')->check();
    }

    public function rules(): array
    {
        return [
            'booking_id'     => ['required', 'uuid', 'exists:bookings,id'],
            'driver_rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'vehicle_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'service_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment'        => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_id.required'     => 'Vui lòng chọn chuyến cần đánh giá',
            'driver_rating.required'  => 'Vui lòng đánh giá tài xế',
            'driver_rating.min'       => 'Đánh giá tối thiểu 1 sao',
            'driver_rating.max'       => 'Đánh giá tối đa 5 sao',
            'vehicle_rating.required' => 'Vui lòng đánh giá xe',
            'service_rating.required' => 'Vui lòng đánh giá dịch vụ',
            'comment.max'             => 'Bình luận tối đa 500 ký tự',
        ];
    }
}
