<?php

namespace App\Listeners;

use App\Events\TripCompleted;
use App\Jobs\UpdateDriverRatingJob;

class UpdateDriverRatingOnTripComplete
{
    public function handle(TripCompleted $event): void
    {
        UpdateDriverRatingJob::dispatch($event->trip->driver_id)
                             ->delay(now()->addHours(1))
                             ->onQueue('default');
    }
}
