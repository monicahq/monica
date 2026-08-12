<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Jobs\PurgeOrphanedFiles;
use App\Models\Contact;
use App\Models\File;
use App\Models\RecordSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurgeOrphanedFilesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_a_file_nothing_has_pointed_at_for_longer_than_the_grace_period(): void
    {
        Queue::fake();
        Storage::fake('local');
        config(['monica.orphaned_file_grace_period_in_days' => 30]);

        $user = User::factory()->create();
        $file = File::factory()->create([
            'account_id' => $user->account_id,
            'orphaned_at' => now()->subDays(31),
        ]);
        Storage::disk('local')->put($file->path, 'anything');

        new PurgeOrphanedFiles()->handle();

        $this->assertSoftDeleted('files', ['id' => $file->id]);
        Storage::disk('local')->assertMissing($file->path);
    }

    /**
     * The gap exists so that restoring a record to how it looked an hour ago
     * still finds the file it pointed at.
     */
    #[Test]
    public function it_leaves_a_file_still_inside_its_grace_period(): void
    {
        Queue::fake();
        Storage::fake('local');
        config(['monica.orphaned_file_grace_period_in_days' => 30]);

        $user = User::factory()->create();
        $file = File::factory()->create([
            'account_id' => $user->account_id,
            'orphaned_at' => now()->subDays(2),
        ]);

        new PurgeOrphanedFiles()->handle();

        $this->assertNotSoftDeleted('files', ['id' => $file->id]);
    }

    #[Test]
    public function it_leaves_a_file_something_points_at(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $file = File::factory()->used(1)->create([
            'account_id' => $user->account_id,
            'orphaned_at' => now()->subYear(),
        ]);

        new PurgeOrphanedFiles()->handle();

        $this->assertNotSoftDeleted('files', ['id' => $file->id]);
    }

    /**
     * A snapshot naming a file is somebody who may still ask for the state that
     * held it, which is exactly what the grace period was for.
     */
    #[Test]
    public function it_leaves_a_file_a_snapshot_still_names(): void
    {
        Queue::fake();
        Storage::fake('local');
        config(['monica.orphaned_file_grace_period_in_days' => 30]);

        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);
        $file = File::factory()->create([
            'account_id' => $user->account_id,
            'orphaned_at' => now()->subYear(),
        ]);
        RecordSnapshot::factory()->holding([$file->id])->create([
            'snapshotable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
        ]);

        new PurgeOrphanedFiles()->handle();

        $this->assertNotSoftDeleted('files', ['id' => $file->id]);
    }

    #[Test]
    public function it_gives_the_bytes_back_to_the_account_it_took_them_from(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $user->account->update(['storage_used_in_bytes' => 4096]);
        File::factory()->create([
            'account_id' => $user->account_id,
            'size_in_bytes' => 1024,
            'orphaned_at' => now()->subYear(),
        ]);

        new PurgeOrphanedFiles()->handle();

        $this->assertEquals(3072, $user->account->fresh()->storage_used_in_bytes);
    }

    #[Test]
    public function it_logs_the_sweep_once_for_the_account_rather_than_once_per_file(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        File::factory()->count(3)->create([
            'account_id' => $user->account_id,
            'orphaned_at' => now()->subYear(),
        ]);

        new PurgeOrphanedFiles()->handle();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::OrphanedFilesSwept
                && $job->user === null
                && $job->parameters === ['count' => 3],
        );
    }

    #[Test]
    public function it_leaves_the_files_of_another_account_where_they_are(): void
    {
        Queue::fake();
        Storage::fake('local');

        $mine = File::factory()->create(['orphaned_at' => now()->subYear()]);
        $theirs = File::factory()->used(1)->create(['orphaned_at' => now()->subYear()]);

        new PurgeOrphanedFiles()->handle();

        $this->assertSoftDeleted('files', ['id' => $mine->id]);
        $this->assertNotSoftDeleted('files', ['id' => $theirs->id]);
    }
}
