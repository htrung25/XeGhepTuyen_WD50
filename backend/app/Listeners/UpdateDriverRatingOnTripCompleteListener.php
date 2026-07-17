<?php

namespace App\Listeners;

use App\Events\TripCompletedEvent;
use App\Jobs\UpdateDriverRatingJob;

class UpdateDriverRatingOnTripCompleteListener
{
    public function handle(TripCompletedEvent $event): void
    {
        UpdateDriverRatingJob::dispatch($event->trip->driver_id)
            ->delay(now()->addHours(1))
            ->onQueue('default');
    }
}
