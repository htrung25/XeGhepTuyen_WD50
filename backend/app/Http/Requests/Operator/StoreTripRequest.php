<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;

class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('operator')->check();
    }

    private function minLeadMinutes(): int
    {
        return (int) config('booking.min_lead_minutes', 30);
    }

    public function rules(): array
    {
        return [
            'route_id' => ['required', 'uuid', 'exists:routes,id'],
            'vehicle_id' => ['required', 'uuid', 'exists:vehicles,id'],
            'driver_id' => ['required', 'uuid', 'exists:drivers,id'],
            // Không nhận 'price' từ client: giá vé chuyến lấy từ tuyến
            // (tuyến lấy từ bảng giá theo km) trong TripService::create.
            'depart_at' => ['required', 'date', 'after:'.now()->addMinutes($this->minLeadMinutes())->toDateTimeString()],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'route_id.required' => 'Vui lòng chọn tuyến đường',
            'vehicle_id.required' => 'Vui lòng chọn xe',
            'driver_id.required' => 'Vui lòng chọn tài xế',
            'depart_at.required' => 'Vui lòng nhập giờ xuất phát',
            'depart_at.after' => 'Giờ xuất phát phải cách hiện tại ít nhất '.$this->minLeadMinutes().' phút',
        ];
    }
}
