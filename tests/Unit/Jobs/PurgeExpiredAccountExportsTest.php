<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\AccountExportStatusEnum;
use App\Jobs\PurgeExpiredAccountExports;
use App\Models\AccountExport;
use App\Models\User;
use App\Notifications\AccountExportFailed;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurgeExpiredAccountExportsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_the_file_of_an_export_past_its_expiry_and_keeps_the_row_marked_expired(): void
    {
        Storage::fake('local');

        $expired = AccountExport::factory()->create([
            'status' => AccountExportStatusEnum::Ready,
            'ready_at' => now()->subDays(8),
            'expires_at' => now()->subDay(),
        ]);
        $current = AccountExport::factory()->create([
            'status' => AccountExportStatusEnum::Ready,
            'ready_at' => now()->subDay(),
            'expires_at' => now()->addDays(6),
        ]);
        Storage::disk('local')->put($expired->path, '{}');
        Storage::disk('local')->put($current->path, '{}');

        new PurgeExpiredAccountExports()->handle();

        $this->assertSame(AccountExportStatusEnum::Expired, $expired->refresh()->status);
        Storage::disk('local')->assertMissing($expired->path);

        $this->assertSame(AccountExportStatusEnum::Ready, $current->refresh()->status);
        Storage::disk('local')->assertExists($current->path);
    }

    #[Test]
    public function it_expires_every_export_when_there_are_more_than_one_page_of_them(): void
    {
        Storage::fake('local');

        $pam = User::factory()->create();
        AccountExport::factory()->count(1_001)->create([
            'user_id' => $pam->id,
            'status' => AccountExportStatusEnum::Ready,
            'ready_at' => now()->subDays(8),
            'expires_at' => now()->subDay(),
        ]);

        new PurgeExpiredAccountExports()->handle();

        $this->assertSame(0, AccountExport::query()->where('status', AccountExportStatusEnum::Ready)->count());
        $this->assertSame(1_001, AccountExport::query()->where('status', AccountExportStatusEnum::Expired)->count());
    }

    #[Test]
    public function it_gives_up_on_an_export_still_being_prepared_after_a_day_and_tells_the_account_holder(): void
    {
        Storage::fake('local');
        Notification::fake();

        $michael = User::factory()->create();
        $stuck = AccountExport::factory()->create(['user_id' => $michael->id, 'status' => AccountExportStatusEnum::Preparing, 'created_at' => now()->subHours(25)]);
        $running = AccountExport::factory()->create(['status' => AccountExportStatusEnum::Preparing]);
        Storage::disk('local')->put("{$stuck->path}.partial", '{}');

        new PurgeExpiredAccountExports()->handle();

        $this->assertSame(AccountExportStatusEnum::Failed, $stuck->refresh()->status);
        $this->assertSame(AccountExportStatusEnum::Preparing, $running->refresh()->status);
        Storage::disk('local')->assertMissing($stuck->directory());
        Notification::assertSentTo($michael, AccountExportFailed::class, fn (AccountExportFailed $notification): bool => $notification->url === route('settings.accountImports.index'));
        Notification::assertSentTimes(AccountExportFailed::class, 1);
    }

    #[Test]
    public function it_keeps_an_export_it_gave_up_on_until_its_files_are_gone(): void
    {
        $stuck = AccountExport::factory()->create(['status' => AccountExportStatusEnum::Preparing, 'created_at' => now()->subHours(25)]);
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('delete')->andReturnFalse();
        $disk->shouldReceive('deleteDirectory')->andReturnTrue();
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);

        new PurgeExpiredAccountExports()->handle();

        $this->assertSame(AccountExportStatusEnum::Preparing, $stuck->refresh()->status);
    }
}
