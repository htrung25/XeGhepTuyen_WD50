<?php

namespace App\Listeners;

use App\Events\BookingCancelledEvent;
use App\Events\BookingConfirmedEvent;
use App\Events\TripCompletedEvent;
use App\Events\TripDriverReassignedEvent;
use App\Events\TripDriverUnavailableEvent;
use App\Events\TripStartedEvent;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Trip;
use App\Services\OperatorHistoryService;

class RecordOperatorHistoryListener
{
    public function __construct(private readonly OperatorHistoryService $history) {}

    public function handle(object $event): void
    {
        if ($event instanceof BookingConfirmedEvent) {
            $this->bookingConfirmed($event);
        } elseif ($event instanceof BookingCancelledEvent) {
            $this->bookingCancelled($event);
        } elseif ($event instanceof TripStartedEvent) {
            $this->tripStarted($event);
        } elseif ($event instanceof TripCompletedEvent) {
            $this->tripCompleted($event);
        } elseif ($event instanceof TripDriverUnavailableEvent) {
            $this->driverUnavailable($event);
        } elseif ($event instanceof TripDriverReassignedEvent) {
            $this->driverReassigned($event);
        }
    }

    private function bookingConfirmed(BookingConfirmedEvent $event): void
    {
        $booking = Booking::with(['user', 'trip.route', 'trip.vehicle'])->find($event->booking->id);
        if (! $booking?->trip?->route) {
            return;
        }

        $trip = $booking->trip;
        $route = $this->routeLabel($trip);
        $this->history->record(
            operatorId: $trip->route->operator_id,
            category: 'booking',
            action: 'booking_confirmed',
            title: 'Khách vừa đặt chỗ',
            description: "{$booking->contact_name} đặt {$booking->passenger_count} chỗ trên chuyến {$route}.",
            severity: 'success',
            subject: $booking,
            actorUserId: $booking->user_id,
            metadata: [
                'booking_code' => $booking->booking_code,
                'contact_name' => $booking->contact_name,
                'passenger_count' => $booking->passenger_count,
                'trip_id' => $trip->id,
                'plate_number' => $trip->vehicle?->plate_number,
                'route' => $route,
            ],
            dedupeKey: "booking-confirmed:{$booking->id}",
            occurredAt: $booking->confirmed_at ?? now(),
        );
    }

    private function bookingCancelled(BookingCancelledEvent $event): void
    {
        $booking = Booking::with(['user', 'trip.route', 'trip.vehicle'])->find($event->booking->id);
        if (! $booking?->trip?->route) {
            return;
        }

        $trip = $booking->trip;
        $this->history->record(
            operatorId: $trip->route->operator_id,
            category: 'booking',
            action: 'booking_cancelled',
            title: 'Khách hủy chỗ',
            description: "Vé {$booking->booking_code} của {$booking->contact_name} đã bị hủy.",
            severity: 'warning',
            subject: $booking,
            actorUserId: $booking->user_id,
            metadata: [
                'booking_code' => $booking->booking_code,
                'refund_amount' => $event->refundAmount,
                'trip_id' => $trip->id,
                'plate_number' => $trip->vehicle?->plate_number,
                'route' => $this->routeLabel($trip),
            ],
            dedupeKey: "booking-cancelled:{$booking->id}",
            occurredAt: $booking->cancelled_at ?? now(),
        );
    }

    private function tripStarted(TripStartedEvent $event): void
    {
        $trip = $this->loadTrip($event->trip->id);
        if (! $trip) {
            return;
        }

        $actual = $trip->started_at ?? now();
        $delayMinutes = max(0, (int) $trip->depart_at->diffInMinutes($actual, false));
        $late = $delayMinutes > 0;
        $plate = $trip->vehicle?->plate_number ?? 'Chưa rõ biển số';

        $this->history->record(
            operatorId: $trip->route->operator_id,
            category: 'trip',
            action: $late ? 'trip_departure_delayed' : 'trip_departed',
            title: $late ? 'Xe xuất bến trễ' : 'Xe đã xuất bến',
            description: $late
                ? "Xe {$plate} xuất bến trễ {$delayMinutes} phút trên chuyến {$this->routeLabel($trip)}."
                : "Xe {$plate} đã xuất bến chuyến {$this->routeLabel($trip)}.",
            severity: $late ? 'warning' : 'success',
            subject: $trip,
            actorUserId: $trip->driver?->user_id,
            metadata: $this->tripMetadata($trip, $delayMinutes),
            dedupeKey: "trip-started:{$trip->id}",
            occurredAt: $actual,
        );
    }

    private function tripCompleted(TripCompletedEvent $event): void
    {
        $trip = $this->loadTrip($event->trip->id);
        if (! $trip) {
            return;
        }

        $actual = $trip->completed_at ?? now();
        $delayMinutes = max(0, (int) $trip->arrive_at->diffInMinutes($actual, false));
        $late = $delayMinutes > 0;
        $plate = $trip->vehicle?->plate_number ?? 'Chưa rõ biển số';

        $this->history->record(
            operatorId: $trip->route->operator_id,
            category: 'trip',
            action: $late ? 'trip_arrival_delayed' : 'trip_arrived',
            title: $late ? 'Xe đến trễ' : 'Xe đã đến nơi',
            description: $late
                ? "Xe {$plate} đến trễ {$delayMinutes} phút trên chuyến {$this->routeLabel($trip)}."
                : "Xe {$plate} đã hoàn thành chuyến {$this->routeLabel($trip)}.",
            severity: $late ? 'warning' : 'success',
            subject: $trip,
            actorUserId: $trip->driver?->user_id,
            metadata: $this->tripMetadata($trip, $delayMinutes),
            dedupeKey: "trip-completed:{$trip->id}",
            occurredAt: $actual,
        );
    }

    private function driverUnavailable(TripDriverUnavailableEvent $event): void
    {
        $trip = $this->loadTrip($event->tripId);
        if (! $trip) {
            return;
        }

        $driver = Driver::with('user')->find($event->reportedDriverId);
        $isVehicleIssue = $event->issueType === 'vehicle';
        $plate = $trip->vehicle?->plate_number ?? 'Chưa rõ biển số';
        $driverName = $driver?->user?->full_name ?? 'Tài xế';

        $this->history->record(
            operatorId: $trip->route->operator_id,
            category: $isVehicleIssue ? 'vehicle' : 'driver',
            action: $isVehicleIssue ? 'vehicle_issue_reported' : 'driver_issue_reported',
            title: $isVehicleIssue ? 'Xe gặp vấn đề' : 'Tài xế gặp vấn đề',
            description: $isVehicleIssue
                ? "Xe {$plate} được báo gặp sự cố: {$event->reason}."
                : "{$driverName} báo không thể chạy chuyến: {$event->reason}.",
            severity: 'danger',
            subject: $trip,
            actorUserId: $driver?->user_id,
            metadata: array_merge($this->tripMetadata($trip), [
                'issue_type' => $event->issueType,
                'reason' => $event->reason,
                'incident_id' => $event->incidentId,
            ]),
            dedupeKey: 'trip-incident:'.($event->incidentId ?? $event->eventId),
        );
    }

    private function driverReassigned(TripDriverReassignedEvent $event): void
    {
        $trip = $this->loadTrip($event->tripId);
        if (! $trip) {
            return;
        }

        $drivers = Driver::with('user')->whereIn('id', [$event->oldDriverId, $event->newDriverId])->get()->keyBy('id');
        $oldName = $drivers->get($event->oldDriverId)?->user?->full_name ?? 'Tài xế cũ';
        $newName = $drivers->get($event->newDriverId)?->user?->full_name ?? 'Tài xế mới';

        $this->history->record(
            operatorId: $trip->route->operator_id,
            category: 'driver',
            action: 'trip_driver_reassigned',
            title: 'Đã đổi tài xế cho chuyến',
            description: "Đã đổi tài xế từ {$oldName} sang {$newName} cho chuyến {$this->routeLabel($trip)}.",
            severity: 'info',
            subject: $trip,
            actorUserId: $event->actorUserId,
            metadata: array_merge($this->tripMetadata($trip), [
                'old_driver_id' => $event->oldDriverId,
                'old_driver_name' => $oldName,
                'new_driver_id' => $event->newDriverId,
                'new_driver_name' => $newName,
            ]),
            dedupeKey: "trip-driver-reassigned:{$event->eventId}",
        );
    }

    private function loadTrip(string $tripId): ?Trip
    {
        return Trip::with(['route', 'vehicle', 'driver.user'])->find($tripId);
    }

    /** @return array<string, mixed> */
    private function tripMetadata(Trip $trip, int $delayMinutes = 0): array
    {
        return [
            'trip_id' => $trip->id,
            'route' => $this->routeLabel($trip),
            'plate_number' => $trip->vehicle?->plate_number,
            'driver_name' => $trip->driver?->user?->full_name,
            'scheduled_depart_at' => $trip->depart_at?->toIso8601String(),
            'scheduled_arrive_at' => $trip->arrive_at?->toIso8601String(),
            'delay_minutes' => $delayMinutes,
        ];
    }

    private function routeLabel(Trip $trip): string
    {
        $origin = collect([$trip->route?->origin_district, $trip->route?->origin_city])->filter()->join(', ');
        $destination = collect([$trip->route?->dest_district, $trip->route?->dest_city])->filter()->join(', ');

        return "{$origin} → {$destination}";
    }
}
