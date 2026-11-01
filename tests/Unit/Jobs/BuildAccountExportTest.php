<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\AccountExportStatusEnum;
use App\Jobs\BuildAccountExport;
use App\Models\AccountExport;
use App\Models\User;
use App\Models\Vault;
use App\Notifications\AccountExportFailed;
use App\Notifications\AccountExportReady;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class BuildAccountExportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_the_file_and_tells_the_account_holder_it_is_ready_for_seven_days(): void
    {
        Storage::fake('local');
        Notification::fake();

        $pam = User::factory()->create();
        $scranton = Vault::factory()->create(['account_id' => $pam->account_id, 'name' => 'Scranton']);
        $export = AccountExport::factory()->create(['user_id' => $pam->id, 'vault_ids' => [$scranton->id]]);

        new BuildAccountExport(export: $export)->handle();

        $export->refresh();

        Storage::disk('local')->assertExists($export->path);
        $this->assertSame(AccountExportStatusEnum::Ready, $export->status);
        $this->assertSame(Storage::disk('local')->size($export->path), $export->size_in_bytes);
        $this->assertEqualsWithDelta(now()->timestamp, $export->ready_at?->timestamp, 2);
        $this->assertEqualsWithDelta(now()->addDays(7)->timestamp, $export->expires_at?->timestamp, 2);
        Notification::assertSentTo($pam, AccountExportReady::class, fn (AccountExportReady $notification): bool => $notification->url === route('settings.accountImports.index'));
    }

    #[Test]
    public function it_builds_nothing_again_once_the_export_is_ready(): void
    {
        Storage::fake('local');
        Notification::fake();

        $export = AccountExport::factory()->create(['status' => AccountExportStatusEnum::Ready]);

        new BuildAccountExport(export: $export)->handle();

        Storage::disk('local')->assertMissing($export->path);
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_puts_a_copy_back_on_the_queue_while_another_copy_holds_the_export(): void
    {
        $export = AccountExport::factory()->create();
        $job = new BuildAccountExport(export: $export)->withFakeQueueInteractions();
        $middleware = $job->middleware()[0];
        Cache::lock($middleware->getLockKey($job), 60)->get();

        $middleware->handle($job, fn () => $this->fail('The copy ran while another copy held the export.'));

        $job->assertReleased(delay: 60);
    }

    #[Test]
    public function it_keeps_one_file_and_sends_one_email_when_the_queue_starts_the_export_a_second_time(): void
    {
        Storage::fake('local');
        Notification::fake();

        $pam = User::factory()->create();
        $scranton = Vault::factory()->create(['account_id' => $pam->account_id, 'name' => 'Scranton']);
        $export = AccountExport::factory()->create(['user_id' => $pam->id, 'vault_ids' => [$scranton->id]]);
        $secondRun = new BuildAccountExport(export: AccountExport::query()->findOrFail($export->id));
        $hasSecondRunStarted = false;

        Vault::retrieved(function () use ($secondRun, &$hasSecondRunStarted): void {
            if ($hasSecondRunStarted) {
                return;
            }

            $hasSecondRunStarted = true;
            $secondRun->handle();
        });

        new BuildAccountExport(export: $export)->handle();

        $export->refresh();

        $this->assertTrue($hasSecondRunStarted);
        $this->assertSame(AccountExportStatusEnum::Ready, $export->status);
        $this->assertSame([$export->path], Storage::disk('local')->allFiles());
        $this->assertSame(Storage::disk('local')->size($export->path), $export->size_in_bytes);
        Notification::assertSentToTimes($pam, AccountExportReady::class, 1);
    }

    #[Test]
    public function it_marks_the_export_failed_and_tells_the_account_holder(): void
    {
        Notification::fake();

        $dwight = User::factory()->create();
        $export = AccountExport::factory()->create(['user_id' => $dwight->id]);

        new BuildAccountExport(export: $export)->failed(new RuntimeException('The disk is full.'));

        $this->assertSame(AccountExportStatusEnum::Failed, $export->refresh()->status);
        Notification::assertSentTo($dwight, AccountExportFailed::class, fn (AccountExportFailed $notification): bool => $notification->url === route('settings.accountImports.index'));
    }

    #[Test]
    public function it_leaves_a_ready_export_alone_when_a_late_attempt_is_marked_failed(): void
    {
        Storage::fake('local');
        Notification::fake();

        $export = AccountExport::factory()->create(['status' => AccountExportStatusEnum::Ready]);
        Storage::disk('local')->put($export->path, '{"version":"2.0-preview.1"}');

        new BuildAccountExport(export: $export)->failed(new RuntimeException('The job has been attempted too many times.'));

        $this->assertSame(AccountExportStatusEnum::Ready, $export->refresh()->status);
        Storage::disk('local')->assertExists($export->path);
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_keeps_the_earlier_export_of_the_account_until_it_expires(): void
    {
        Storage::fake('local');
        Notification::fake();

        $pam = User::factory()->create();
        $scranton = Vault::factory()->create(['account_id' => $pam->account_id, 'name' => 'Scranton']);
        $earlier = AccountExport::factory()->create([
            'user_id' => $pam->id,
            'status' => AccountExportStatusEnum::Ready,
            'ready_at' => now()->subDay(),
            'expires_at' => now()->addDays(6),
        ]);
        Storage::disk('local')->put($earlier->path, '{"version":"2.0-preview.1"}');
        $export = AccountExport::factory()->create(['user_id' => $pam->id, 'vault_ids' => [$scranton->id]]);

        new BuildAccountExport(export: $export)->handle();

        $this->assertSame(AccountExportStatusEnum::Ready, $earlier->refresh()->status);
        Storage::disk('local')->assertExists($earlier->path);
        Storage::disk('local')->assertExists($export->refresh()->path);
    }
}
