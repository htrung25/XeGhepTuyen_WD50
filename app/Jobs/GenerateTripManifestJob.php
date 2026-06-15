<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\Trip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateTripManifestJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly Trip $trip)
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $trip = $this->trip->load([
            'route',
            'vehicle',
            'driver.user',
            'bookings' => fn($q) => $q->whereIn('booking_status', [
                BookingStatus::Confirmed->value,
                BookingStatus::CheckedIn->value,
            ])->with(['passengers', 'pickupStop', 'dropoffStop']),
        ]);

        $pdf = Pdf::loadView('pdfs.trip-manifest', ['trip' => $trip]);

        $filename = "manifests/trip_{$trip->id}_{now()->format('Ymd')}.pdf";
        Storage::disk('public')->put($filename, $pdf->output());

        Log::info("Manifest generated for trip {$trip->id}", ['path' => $filename]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('GenerateTripManifestJob thất bại', [
            'trip_id' => $this->trip->id,
            'error'   => $e->getMessage(),
        ]);
    }
}
