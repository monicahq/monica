<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyFile;
use App\Enums\UserActionEnum;
use App\Exceptions\CannotDeleteAFileInUse;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyFileTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_the_file_off_the_disk(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $file = $this->fileOnDisk($user);

        new DestroyFile(account: $user->account, file: $file, user: $user)->execute();

        Storage::disk('local')->assertMissing($file->path);
    }

    #[Test]
    public function it_keeps_the_row_that_says_the_file_was_there(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $file = $this->fileOnDisk($user);

        new DestroyFile(account: $user->account, file: $file, user: $user)->execute();

        $this->assertSoftDeleted('files', ['id' => $file->id]);
    }

    #[Test]
    public function it_gives_the_bytes_back_to_the_account(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $user->account->update(['storage_used_in_bytes' => 5000]);
        $file = $this->fileOnDisk($user, 2000);

        new DestroyFile(account: $user->account, file: $file, user: $user)->execute();

        $this->assertEquals(3000, $user->account->fresh()->storage_used_in_bytes);
    }

    #[Test]
    public function it_never_takes_the_account_below_nothing(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $user->account->update(['storage_used_in_bytes' => 100]);
        $file = $this->fileOnDisk($user, 2000);

        new DestroyFile(account: $user->account, file: $file, user: $user)->execute();

        $this->assertEquals(100, $user->account->fresh()->storage_used_in_bytes);
    }

    #[Test]
    public function it_refuses_a_file_something_still_points_at(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $file = File::factory()->used(2)->create(['account_id' => $user->account_id]);

        $this->expectException(CannotDeleteAFileInUse::class);

        new DestroyFile(account: $user->account, file: $file, user: $user)->execute();
    }

    #[Test]
    public function it_refuses_a_file_in_another_account(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $file = File::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyFile(account: $user->account, file: $file, user: $user)->execute();
    }

    #[Test]
    public function it_is_taken_by_nobody_in_particular_when_the_sweep_does_it(): void
    {
        Queue::fake();
        Storage::fake('local');

        $account = Account::factory()->create();
        $file = File::factory()->create(['account_id' => $account->id]);

        new DestroyFile(account: $account, file: $file)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::FileDeletion
                && $job->user === null,
        );
    }

    #[Test]
    public function it_logs_the_deletion_with_the_name_the_file_had(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $file = $this->fileOnDisk($user);

        new DestroyFile(account: $user->account, file: $file, user: $user)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::FileDeletion
                && $job->parameters === ['name' => 'contract.pdf'],
        );
    }

    private function fileOnDisk(User $user, int $size = 2048): File
    {
        $file = File::factory()->create([
            'account_id' => $user->account_id,
            'original_name' => 'contract.pdf',
            'size_in_bytes' => $size,
        ]);

        Storage::disk('local')->put($file->path, 'anything');

        return $file;
    }
}
