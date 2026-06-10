<?php

namespace App\Providers;

use App\Events\BookingCancelled;
use App\Events\BookingConfirmed;
use App\Events\DriverLocationUpdated;
use App\Events\PaymentProcessed;
use App\Events\TripCompleted;
use App\Events\TripStarted;
use App\Listeners\BroadcastDriverLocation;
use App\Listeners\NotifyPassengersOnTripStart;
use App\Listeners\SendBookingCancellationNotification;
use App\Listeners\SendBookingConfirmationNotification;
use App\Listeners\UpdateDriverRatingOnTripComplete;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        BookingConfirmed::class => [
            SendBookingConfirmationNotification::class,
        ],

        BookingCancelled::class => [
            SendBookingCancellationNotification::class,
        ],

        TripStarted::class => [
            NotifyPassengersOnTripStart::class,
        ],

        TripCompleted::class => [
            UpdateDriverRatingOnTripComplete::class,
        ],

        DriverLocationUpdated::class => [
            BroadcastDriverLocation::class,
        ],
    ];
}
