<?php

namespace App\Console\Commands;

use App\Models\Driver;
use App\Models\Operator;
use App\Models\PartnerApplication;
use App\Services\PrivateDocumentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class MigrateDocumentsToPrivateStorageCommand extends Command
{
    protected $signature = 'documents:migrate-private
        {--dry-run : Chỉ thống kê, không sao chép hoặc cập nhật DB}
        {--delete-source : Xóa file public sau khi mọi bản ghi đã cập nhật thành công}';

    protected $description = 'Chuyển giấy tờ định danh cũ từ public disk sang private document disk';

    /** @var array<string, string> */
    private array $migrated = [];

    /** @var array<string, true> */
    private array $sourcesToDelete = [];

    private int $updated = 0;

    private int $missing = 0;

    public function __construct(private readonly PrivateDocumentService $documents)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        Driver::query()->chunkById(100, function ($drivers): void {
            foreach ($drivers as $driver) {
                $this->migrateModelFields($driver, [
                    'id_card_front_path',
                    'id_card_back_path',
                    'license_front_path',
                ]);
            }
        });

        PartnerApplication::query()->chunkById(100, function ($applications): void {
            foreach ($applications as $application) {
                $changes = [];
                $licensePath = $this->migrateValue($application->business_license_path);
                if ($licensePath !== $application->business_license_path) {
                    $changes['business_license_path'] = $licensePath;
                }

                $fleetPaths = collect($application->fleet_image_paths ?? [])
                    ->map(fn (?string $path): ?string => $this->migrateValue($path))
                    ->filter()
                    ->values()
                    ->all();
                if ($fleetPaths !== ($application->fleet_image_paths ?? [])) {
                    $changes['fleet_image_paths'] = $fleetPaths;
                }

                $this->persist($application, $changes);
            }
        });

        Operator::query()->chunkById(100, function ($operators): void {
            foreach ($operators as $operator) {
                $this->migrateModelFields($operator, ['license_path']);
            }
        });

        if (! $this->option('dry-run') && $this->option('delete-source')) {
            Storage::disk('public')->delete(array_keys($this->sourcesToDelete));
        }

        $this->info("Hoàn tất: {$this->updated} bản ghi được cập nhật, {$this->missing} file nguồn không tìm thấy.");

        if (! $this->option('delete-source')) {
            $this->warn('File public nguồn chưa bị xóa. Chạy lại với --delete-source sau khi kiểm tra kết quả.');
        }

        return self::SUCCESS;
    }

    /** @param  array<int, string>  $fields */
    private function migrateModelFields(object $model, array $fields): void
    {
        $changes = [];

        foreach ($fields as $field) {
            $migrated = $this->migrateValue($model->{$field});
            if ($migrated !== $model->{$field}) {
                $changes[$field] = $migrated;
            }
        }

        $this->persist($model, $changes);
    }

    private function migrateValue(?string $value): ?string
    {
        if (! $value || str_starts_with($value, PrivateDocumentService::ROOT.'/')) {
            return $value;
        }

        if (isset($this->migrated[$value])) {
            return $this->migrated[$value];
        }

        $sourcePath = $this->publicPath($value);
        if (! $sourcePath || ! Storage::disk('public')->exists($sourcePath)) {
            $this->missing++;
            $this->warn("Không tìm thấy file public: {$value}");

            return $value;
        }

        $targetPath = PrivateDocumentService::ROOT.'/legacy/'.Str::uuid().'-'.basename($sourcePath);
        if (! $this->option('dry-run')) {
            $stream = Storage::disk('public')->readStream($sourcePath);
            if (! is_resource($stream)) {
                throw new RuntimeException("Không thể đọc file public: {$sourcePath}");
            }

            try {
                $written = Storage::disk($this->documents->disk())->writeStream(
                    $targetPath,
                    $stream,
                );
            } finally {
                fclose($stream);
            }

            if (! $written) {
                throw new RuntimeException("Không thể ghi file private: {$targetPath}");
            }
        }

        $this->migrated[$value] = $targetPath;
        $this->sourcesToDelete[$sourcePath] = true;

        return $targetPath;
    }

    /** @param  array<string, mixed>  $changes */
    private function persist(object $model, array $changes): void
    {
        if ($changes === []) {
            return;
        }

        $this->updated++;
        if (! $this->option('dry-run')) {
            $model->update($changes);
        }
    }

    private function publicPath(string $value): ?string
    {
        $path = parse_url($value, PHP_URL_PATH);
        if (! is_string($path)) {
            return null;
        }

        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = Str::after($path, 'storage/');
        }

        return $path !== '' ? $path : null;
    }
}
