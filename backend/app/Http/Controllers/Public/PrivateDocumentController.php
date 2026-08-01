<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\PrivateDocumentService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateDocumentController extends Controller
{
    public function __construct(private readonly PrivateDocumentService $documents) {}

    public function show(Request $request): StreamedResponse
    {
        try {
            $path = Crypt::decryptString((string) $request->query('file'));
        } catch (DecryptException) {
            abort(404);
        }

        if (! str_starts_with($path, PrivateDocumentService::ROOT.'/')) {
            abort(404);
        }

        $disk = Storage::disk($this->documents->disk());
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Referrer-Policy' => 'no-referrer',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
