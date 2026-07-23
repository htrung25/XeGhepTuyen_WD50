<?php

namespace App\Providers;

use App\Events\BookingCancelledEvent;
use App\Events\BookingConfirmedEvent;
use App\Events\DriverLocationUpdatedEvent;
use App\Events\TripCompletedEvent;
use App\Events\TripDriverReassignedEvent;
use App\Events\TripDriverUnavailableEvent;
use App\Events\TripStartedEvent;
use App\Listeners\BroadcastDriverLocationListener;
use App\Listeners\NotifyPassengersOnTripStartListener;
use App\Listeners\SendBookingCancellationNotificationListener;
use App\Listeners\SendBookingConfirmationNotificationListener;
use App\Listeners\SendTripDriverReassignedNotificationListener;
use App\Listeners\SendTripDriverUnavailableNotificationListener;
use App\Listeners\UpdateDriverRatingOnTripCompleteListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        BookingConfirmedEvent::class => [
            SendBookingConfirmationNotificationListener::class,
        ],

        BookingCancelledEvent::class => [
            SendBookingCancellationNotificationListener::class,
        ],

        TripStartedEvent::class => [
            NotifyPassengersOnTripStartListener::class,
        ],

        TripCompletedEvent::class => [
            UpdateDriverRatingOnTripCompleteListener::class,
        ],

        TripDriverUnavailableEvent::class => [
            SendTripDriverUnavailableNotificationListener::class,
        ],

        TripDriverReassignedEvent::class => [
            SendTripDriverReassignedNotificationListener::class,
        ],

        DriverLocationUpdatedEvent::class => [
            BroadcastDriverLocationListener::class,
        ],
    ];
}
