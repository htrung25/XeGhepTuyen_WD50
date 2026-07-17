<?php

namespace App\Enums;

enum NotificationTypeEnum: string
{
    case BookingConfirmedEvent = 'booking_confirmed';
    case BookingCancelledEvent = 'booking_cancelled';
    case TripReminder = 'trip_reminder';
    case DriverArriving = 'driver_arriving';
    case TripStartedEvent = 'trip_started';
    case TripCompletedEvent = 'trip_completed';
    case PaymentSuccess = 'payment_success';
    case RefundProcessed = 'refund_processed';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::BookingConfirmedEvent => 'Đặt vé thành công',
            self::BookingCancelledEvent => 'Hủy vé',
            self::TripReminder => 'Nhắc nhở chuyến đi',
            self::DriverArriving => 'Tài xế sắp đến',
            self::TripStartedEvent => 'Chuyến đã xuất phát',
            self::TripCompletedEvent => 'Chuyến hoàn thành',
            self::PaymentSuccess => 'Thanh toán thành công',
            self::RefundProcessed => 'Hoàn tiền thành công',
            self::System => 'Thông báo hệ thống',
        };
    }
}
