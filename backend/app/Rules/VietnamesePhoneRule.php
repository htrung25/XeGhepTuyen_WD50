<?php

namespace App\Rules;

/**
 * Nguồn duy nhất cho pattern số điện thoại VN hợp lệ (10 số, đầu số 03/05/07/08/09).
 * Dùng dạng `'regex:'.VietnamesePhoneRule::PATTERN` để giữ nguyên rule name `regex`
 * (không đổi key thông báo lỗi `phone.regex` đang được override trong các FormRequest).
 */
final class VietnamesePhoneRule
{
    public const PATTERN = '/^(0[35789])[0-9]{8}$/';
}
