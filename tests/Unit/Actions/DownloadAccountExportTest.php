<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DownloadAccountExport;
use App\Enums\AccountExportStatusEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\AccountExport;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DownloadAccountExportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hands_over_a_ready_export_and_writes_the_download_in_the_changelog(): void
    {
        Queue::fake();

        $pam = User::factory()->create();
        $export = $this->readyExportOf($pam);

        $downloaded = new DownloadAccountExport(user: $pam, export: $export)->execute();

        $this->assertTrue($downloaded->is($export));
        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::AccountExportDownloaded
                && $job->user->id === $pam->id
                && $job->parameters === ['count' => 2],
        );
    }

    #[Test]
    public function it_refuses_an_export_of_another_account(): void
    {
        Queue::fake();

        $pam = User::factory()->create();
        $export = $this->readyExportOf(User::factory()->create());

        $this->expectException(ModelNotFoundException::class);

        new DownloadAccountExport(user: $pam, export: $export)->execute();
    }

    #[Test]
    public function it_answers_not_found_for_an_export_still_being_prepared(): void
    {
        Queue::fake();

        $pam = User::factory()->create();
        $export = AccountExport::factory()->create(['user_id' => $pam->id, 'status' => AccountExportStatusEnum::Preparing]);

        try {
            new DownloadAccountExport(user: $pam, export: $export)->execute();

            $this->fail('An export still being prepared was handed over.');
        } catch (AuthorizationException $exception) {
            $this->assertSame(404, $exception->status());
        }

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_answers_not_found_when_the_file_of_a_ready_export_is_gone(): void
    {
        Queue::fake();

        $pam = User::factory()->create();
        $export = $this->readyExportOf($pam);
        Storage::disk('local')->delete($export->path);

        $this->expectException(ModelNotFoundException::class);

        new DownloadAccountExport(user: $pam, export: $export)->execute();
    }

    private function readyExportOf(User $user): AccountExport
    {
        Storage::fake('local');

        $export = AccountExport::factory()->create([
            'user_id' => $user->id,
            'vault_ids' => [1, 2],
            'status' => AccountExportStatusEnum::Ready,
            'ready_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        Storage::disk('local')->put($export->path, '{"version":"2.0-preview.1"}');

        return $export;
    }
}
