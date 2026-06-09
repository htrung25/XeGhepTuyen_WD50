<?php

namespace App\Enums;

enum NotificationType: string
{
    case BookingConfirmed   = 'booking_confirmed';
    case BookingCancelled   = 'booking_cancelled';
    case TripReminder       = 'trip_reminder';
    case DriverArriving     = 'driver_arriving';
    case TripStarted        = 'trip_started';
    case TripCompleted      = 'trip_completed';
    case PaymentSuccess     = 'payment_success';
    case RefundProcessed    = 'refund_processed';
    case System             = 'system';

    public function label(): string
    {
        return match($this) {
            self::BookingConfirmed => 'Đặt vé thành công',
            self::BookingCancelled => 'Hủy vé',
            self::TripReminder     => 'Nhắc nhở chuyến đi',
            self::DriverArriving   => 'Tài xế sắp đến',
            self::TripStarted      => 'Chuyến đã xuất phát',
            self::TripCompleted    => 'Chuyến hoàn thành',
            self::PaymentSuccess   => 'Thanh toán thành công',
            self::RefundProcessed  => 'Hoàn tiền thành công',
            self::System           => 'Thông báo hệ thống',
        };
    }
}
