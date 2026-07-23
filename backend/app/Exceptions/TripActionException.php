<?php

namespace App\Exceptions;

use Exception;

/**
 * Lỗi nghiệp vụ của các transition chuyến (báo nghỉ / đổi tài xế / start / complete / cancel).
 * Mang sẵn mã lỗi (code) + HTTP status để controller map thẳng ra JSON, tránh lặp mapping.
 *
 * 404 (notFound): dùng cho lỗi ownership — KHÔNG tiết lộ chuyến của người khác.
 * 422: lỗi trạng thái/điều kiện nghiệp vụ, kèm mã máy đọc được.
 */
class TripActionException extends Exception
{
    public function __construct(
        public readonly string $errorCode,
        public readonly int $status,
        string $message,
    ) {
        parent::__construct($message);
    }

    // ── 404 — ownership (không lộ chuyến người khác) ──────────────────────────
    public static function notFound(string $message = 'Chuyến đi không tồn tại'): self
    {
        return new self('TRIP_NOT_FOUND', 404, $message);
    }

    // ── Driver report (R2, R3) ────────────────────────────────────────────────
    public static function notReportable(): self
    {
        return new self('TRIP_NOT_REPORTABLE', 422, 'Chuyến này không ở trạng thái có thể báo nghỉ');
    }

    public static function reportCutoffPassed(int $minutes): self
    {
        return new self('REPORT_CUTOFF_PASSED', 422, "Còn dưới {$minutes} phút trước giờ chạy, vui lòng liên hệ trực tiếp nhà xe");
    }

    // ── Operator reassign (A2..A7) ────────────────────────────────────────────
    public static function notReassignable(): self
    {
        return new self('TRIP_NOT_REASSIGNABLE', 422, 'Chuyến này không ở trạng thái có thể đổi tài xế');
    }

    public static function alreadyDeparted(): self
    {
        return new self('TRIP_ALREADY_DEPARTED', 422, 'Không thể đổi tài xế sau giờ khởi hành');
    }

    public static function driverNotInOperator(): self
    {
        return new self('DRIVER_NOT_IN_OPERATOR', 422, 'Tài xế không thuộc nhà xe của bạn hoặc đã ngừng hoạt động');
    }

    public static function driverNotEligible(): self
    {
        return new self('DRIVER_NOT_ELIGIBLE', 422, 'Tài xế chưa được duyệt hoặc GPLX đã hết hạn');
    }

    public static function driverUnchanged(): self
    {
        return new self('DRIVER_UNCHANGED', 422, 'Tài xế mới trùng với tài xế hiện tại');
    }

    public static function driverScheduleConflict(): self
    {
        return new self('DRIVER_SCHEDULE_CONFLICT', 422, 'Tài xế mới đã có chuyến trùng khung giờ này');
    }

    // ── Start / payment guard (6.3, 6.4) ─────────────────────────────────────
    public static function awaitingReassignment(): self
    {
        return new self('TRIP_AWAITING_REASSIGNMENT', 422, 'Chuyến đang sắp xếp lại tài xế, vui lòng thử lại sau');
    }

    public static function notStartable(): self
    {
        return new self('TRIP_NOT_STARTABLE', 422, 'Không thể bắt đầu chuyến này');
    }

    public static function notCompletable(): self
    {
        return new self('TRIP_NOT_COMPLETABLE', 422, 'Chuyến chưa được bắt đầu');
    }
}
