<?php

namespace App\Services;

use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class PrivateDocumentService
{
    public const ROOT = 'private-documents';

    public function store(UploadedFile $file, string $directory): string
    {
        $directory = trim($directory, '/');
        $path = Storage::disk($this->disk())->putFile(
            self::ROOT.'/'.$directory,
            $file,
        );

        if (! is_string($path)) {
            throw new RuntimeException('Không thể lưu tài liệu riêng tư');
        }

        return $path;
    }

    public function temporaryUrl(?string $path, ?DateTimeInterface $expiresAt = null): ?string
    {
        if (! $path) {
            return null;
        }

        // Giữ tương thích trong cửa sổ rollout trước khi command migrate file cũ chạy.
        if (! str_starts_with($path, self::ROOT.'/')) {
            if (
                str_starts_with($path, 'http://')
                || str_starts_with($path, 'https://')
                || str_starts_with($path, '/')
            ) {
                return $path;
            }

            return Storage::disk('public')->url($path);
        }

        $expiresAt ??= now()->addMinutes((int) config('documents.url_ttl_minutes', 5));

        return URL::temporarySignedRoute('private-documents.show', $expiresAt, [
            'file' => Crypt::encryptString($path),
        ]);
    }

    public function delete(?string $path): void
    {
        if ($path && str_starts_with($path, self::ROOT.'/')) {
            Storage::disk($this->disk())->delete($path);
        }
    }

    /** @param  iterable<?string>  $paths */
    public function deleteMany(iterable $paths): void
    {
        foreach ($paths as $path) {
            $this->delete($path);
        }
    }

    public function disk(): string
    {
        $disk = (string) config('documents.disk', 'local');

        if (
            config('app.env') === 'production'
            && $disk === 'local'
            && ! config('documents.allow_local_in_production', false)
        ) {
            throw new RuntimeException(
                'Private document storage chưa được cấu hình bằng object storage trên production',
            );
        }

        return $disk;
    }
}
