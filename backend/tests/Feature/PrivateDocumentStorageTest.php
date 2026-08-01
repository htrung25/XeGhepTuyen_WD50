<?php

use App\Models\Driver;
use App\Models\Operator;
use App\Models\PartnerApplication;
use App\Models\User;
use App\Services\PrivateDocumentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    config(['documents.disk' => 'local']);
    Storage::fake('local');
    Storage::fake('public');
});

it('stores driver identity documents privately and serves only a temporary signed URL', function (): void {
    $operator = Operator::create([
        'user_id' => User::factory()->operator()->create()->id,
        'company_name' => 'Nhà xe bảo mật',
        'business_license' => 'GPKD-PRIVATE',
        'status' => 'verified',
    ]);

    $this->post('/api/driver/auth/register', [
        'full_name' => 'Tài xế bảo mật',
        'phone' => '0907654001',
        'password' => 'Driver@123',
        'password_confirmation' => 'Driver@123',
        'operator_id' => $operator->id,
        'license_number' => 'B2-PRIVATE-1',
        'license_class' => 'B2',
        'license_expiry' => now()->addYear()->toDateString(),
        'id_card_number' => '001999999991',
        'id_card_front' => UploadedFile::fake()->image('front.jpg'),
        'id_card_back' => UploadedFile::fake()->image('back.jpg'),
        'license_front' => UploadedFile::fake()->image('license.jpg'),
    ])->assertCreated();

    $driver = Driver::where('license_number', 'B2-PRIVATE-1')->firstOrFail();

    expect($driver->id_card_front_path)->toStartWith('private-documents/drivers/')
        ->and($driver->id_card_back_path)->toStartWith('private-documents/drivers/')
        ->and($driver->license_front_path)->toStartWith('private-documents/drivers/');

    Storage::disk('local')->assertExists($driver->id_card_front_path);
    Storage::disk('public')->assertDirectoryEmpty('/');

    $url = app(PrivateDocumentService::class)->temporaryUrl($driver->id_card_front_path);
    $this->get($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private');

    $separator = str_contains($url, '?') ? '&' : '?';
    $this->get($url.$separator.'tampered=1')->assertForbidden();
});

it('stores partner application evidence privately while exposing signed resource URLs', function (): void {
    $this->post('/api/public/partner-applications', [
        'company_name' => 'Nhà xe hồ sơ kín',
        'tax_code' => 'TAX-PRIVATE-1',
        'address' => 'Hà Nội',
        'fleet_breakdown' => ['mpv_7' => 1],
        'representative_name' => 'Nguyễn Văn Đại Diện',
        'phone' => '0907654002',
        'business_license' => UploadedFile::fake()->create('license.pdf', 20, 'application/pdf'),
        'fleet_images' => [UploadedFile::fake()->image('fleet.jpg')],
    ])->assertCreated();

    $application = PartnerApplication::where('tax_code', 'TAX-PRIVATE-1')->firstOrFail();

    expect($application->business_license_path)->toStartWith('private-documents/partner-applications/licenses/')
        ->and($application->fleet_image_paths)->toHaveCount(1)
        ->and($application->fleet_image_paths[0])->toStartWith('private-documents/partner-applications/fleet/');

    Storage::disk('local')->assertExists($application->business_license_path);
    Storage::disk('local')->assertExists($application->fleet_image_paths[0]);
    Storage::disk('public')->assertDirectoryEmpty('/');
});

it('rejects an expired private document URL', function (): void {
    $path = app(PrivateDocumentService::class)->store(
        UploadedFile::fake()->image('identity.jpg'),
        'drivers',
    );
    $url = app(PrivateDocumentService::class)->temporaryUrl($path, now()->addSecond());

    $this->travel(2)->seconds();

    $this->get($url)->assertForbidden();
});

it('never exposes a storage path through the admin driver resource', function (): void {
    $admin = User::factory()->admin()->create(['admin_role_id' => superAdminRole()->id]);
    $operator = Operator::create([
        'user_id' => User::factory()->operator()->create()->id,
        'company_name' => 'Nhà xe resource',
        'business_license' => 'GPKD-RESOURCE',
        'status' => 'verified',
    ]);
    $path = app(PrivateDocumentService::class)->store(
        UploadedFile::fake()->image('identity.jpg'),
        'drivers',
    );
    $driver = Driver::create([
        'user_id' => User::factory()->driver()->create()->id,
        'operator_id' => $operator->id,
        'license_number' => 'B2-RESOURCE',
        'license_class' => 'B2',
        'license_expiry' => now()->addYear(),
        'id_card_number' => '001999999992',
        'id_card_front_path' => $path,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)->getJson('/api/admin/drivers/'.$driver->id)->assertOk();
    $documentUrl = $response->json('data.documents.id_card_front');

    expect($documentUrl)->toContain('/api/public/private-documents')
        ->and($documentUrl)->not->toContain($path);
});

it('migrates legacy public documents and can delete their public source', function (): void {
    Storage::disk('public')->put('documents/legacy-front.jpg', 'legacy-content');
    $operator = Operator::create([
        'user_id' => User::factory()->operator()->create()->id,
        'company_name' => 'Nhà xe legacy',
        'business_license' => 'GPKD-LEGACY',
    ]);
    $driver = Driver::create([
        'user_id' => User::factory()->driver()->create()->id,
        'operator_id' => $operator->id,
        'license_number' => 'B2-LEGACY',
        'license_class' => 'B2',
        'license_expiry' => now()->addYear(),
        'id_card_number' => '001999999993',
        'id_card_front_path' => '/storage/documents/legacy-front.jpg',
        'status' => 'pending',
    ]);

    $this->artisan('documents:migrate-private', ['--delete-source' => true])
        ->assertSuccessful();

    $newPath = $driver->fresh()->id_card_front_path;
    expect($newPath)->toStartWith('private-documents/legacy/');
    Storage::disk('local')->assertExists($newPath);
    Storage::disk('public')->assertMissing('documents/legacy-front.jpg');
});

it('keeps legacy public URLs readable during the deployment migration window', function (): void {
    $service = app(PrivateDocumentService::class);

    expect($service->temporaryUrl('/storage/documents/legacy.jpg'))
        ->toBe('/storage/documents/legacy.jpg');
});

it('refuses ephemeral local document storage in production', function (): void {
    config([
        'app.env' => 'production',
        'documents.disk' => 'local',
        'documents.allow_local_in_production' => false,
    ]);

    expect(fn () => app(PrivateDocumentService::class)->store(
        UploadedFile::fake()->image('identity.jpg'),
        'drivers',
    ))->toThrow(RuntimeException::class, 'object storage');
});
