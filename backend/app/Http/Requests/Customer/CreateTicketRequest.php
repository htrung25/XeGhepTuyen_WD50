<?php

namespace App\Http\Requests\Customer;

use App\Enums\TicketCategoryEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('customer')->check();
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'min:5', 'max:255'],
            'category' => ['required', new Enum(TicketCategoryEnum::class)],
            'booking_code' => [
                'nullable',
                'string',
                'exists:bookings,booking_code',
                // Đảm bảo booking thuộc về chính user đang gửi yêu cầu
                function ($attribute, $value, $fail) {
                    $exists = \DB::table('bookings')
                        ->where('booking_code', $value)
                        ->where('user_id', auth('customer')->id())
                        ->exists();

                    if (! $exists) {
                        $fail('Mã đặt vé liên quan không hợp lệ hoặc không thuộc về tài khoản của bạn.');
                    }
                },
            ],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'Vui lòng nhập tiêu đề hỗ trợ.',
            'subject.min' => 'Tiêu đề hỗ trợ phải có ít nhất 5 ký tự.',
            'category.required' => 'Vui lòng chọn danh mục hỗ trợ.',
            'message.required' => 'Vui lòng nhập nội dung yêu cầu hỗ trợ.',
            'message.min' => 'Nội dung hỗ trợ phải có ít nhất 10 ký tự.',
        ];
    }
}
