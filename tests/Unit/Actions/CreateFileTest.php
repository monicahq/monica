<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateFile;
use App\Enums\UserActionEnum;
use App\Exceptions\NotEnoughStorageLeft;
use App\Jobs\LogUserAction;
use App\Models\File;
use App\Models\StripeOrder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class CreateFileTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_the_file_and_says_what_it_is(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $file = new CreateFile(
            user: $user,
            upload: UploadedFile::fake()->create('contract.pdf', 4, 'application/pdf'),
            vault: $vault,
        )->execute();

        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals($user->account_id, $file->account_id);
        $this->assertEquals($vault->id, $file->vault_id);
        $this->assertEquals('contract.pdf', $file->original_name);
        $this->assertEquals('application/pdf', $file->mime_type);
        $this->assertEquals(4096, $file->size_in_bytes);
        $this->assertEquals(64, mb_strlen($file->checksum));
        $this->assertEquals($user->id, $file->uploaded_by_user_id);

        Storage::disk('local')->assertExists($file->path);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::FileUpload
                && $job->account->id === $user->account_id
                && $job->parameters === ['name' => 'contract.pdf'],
        );
    }

    #[Test]
    public function it_writes_it_under_the_account_and_the_vault(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $file = new CreateFile(
            user: $user,
            upload: UploadedFile::fake()->create('contract.pdf', 1),
            vault: $vault,
        )->execute();

        $this->assertStringStartsWith("accounts/{$user->account_id}/vaults/{$vault->id}/", $file->path);
        $this->assertStringNotContainsString('contract', $file->path);
    }

    #[Test]
    public function it_writes_a_file_of_the_account_under_no_vault(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();

        $file = new CreateFile(user: $user, upload: UploadedFile::fake()->create('scan.pdf', 1))->execute();

        $this->assertNull($file->vault_id);
        $this->assertStringStartsWith("accounts/{$user->account_id}/", $file->path);
    }

    #[Test]
    public function it_counts_the_bytes_against_the_account(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();

        new CreateFile(user: $user, upload: UploadedFile::fake()->create('scan.pdf', 3))->execute();

        $this->assertEquals(3072, $user->account->fresh()->storage_used_in_bytes);
    }

    #[Test]
    public function it_arrives_pointing_at_nothing(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();

        $file = new CreateFile(user: $user, upload: UploadedFile::fake()->create('scan.pdf', 1))->execute();

        $this->assertEquals(0, $file->reference_count);
        $this->assertNotNull($file->orphaned_at);
    }

    #[Test]
    public function it_reads_how_big_a_picture_is(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();

        $file = new CreateFile(
            user: $user,
            upload: UploadedFile::fake()->image('photo.jpg', 320, 200),
        )->execute();

        $this->assertEquals(320, $file->width);
        $this->assertEquals(200, $file->height);
    }

    #[Test]
    public function it_leaves_a_document_without_dimensions(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();

        $file = new CreateFile(user: $user, upload: UploadedFile::fake()->create('scan.pdf', 1))->execute();

        $this->assertNull($file->width);
        $this->assertNull($file->height);
    }

    #[Test]
    public function it_refuses_a_file_the_account_has_no_room_for(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $user->account->update(['storage_limit_in_bytes' => 1024, 'storage_used_in_bytes' => 512]);

        $this->expectException(NotEnoughStorageLeft::class);

        try {
            new CreateFile(user: $user, upload: UploadedFile::fake()->create('scan.pdf', 2))->execute();
        } finally {
            $this->assertEquals(0, File::query()->count());
            $this->assertEquals(512, $user->account->fresh()->storage_used_in_bytes);
        }
    }

    #[Test]
    public function it_measures_the_room_left_after_uploads_that_finished_meanwhile(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $user->account->update(['storage_limit_in_bytes' => 4096, 'storage_used_in_bytes' => 0]);
        DB::table('accounts')->where('id', $user->account_id)->update(['storage_used_in_bytes' => 3072]);

        $this->expectException(NotEnoughStorageLeft::class);

        try {
            new CreateFile(user: $user, upload: UploadedFile::fake()->create('scan.pdf', 2))->execute();
        } finally {
            $this->assertEquals(0, File::query()->count());
            $this->assertSame([], Storage::disk('local')->allFiles());
        }
    }

    #[Test]
    public function it_measures_a_paid_account_against_the_paid_ceiling(): void
    {
        Queue::fake();
        Storage::fake();
        config(['monica.paid_storage_limit_in_bytes' => 3072]);

        $user = User::factory()->create();
        $user->account->update([
            'storage_limit_in_bytes' => 1024,
            'storage_used_in_bytes' => 512,
        ]);
        StripeOrder::factory()->settled()->create(['account_id' => $user->account_id]);

        $file = new CreateFile(user: $user, upload: UploadedFile::fake()->create('scan.pdf', 2))->execute();

        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals(1024, $user->account->fresh()->storage_limit_in_bytes);
        $this->assertEquals(3072, $user->account->fresh()->storageLimitInBytes());
    }

    #[Test]
    public function it_refuses_a_paid_account_past_the_paid_ceiling(): void
    {
        Queue::fake();
        Storage::fake();
        config(['monica.paid_storage_limit_in_bytes' => 3072]);

        $this->expectException(NotEnoughStorageLeft::class);

        $user = User::factory()->create();
        $user->account->update([
            'storage_limit_in_bytes' => 1024,
            'storage_used_in_bytes' => 3000,
        ]);
        StripeOrder::factory()->settled()->create(['account_id' => $user->account_id]);

        try {
            new CreateFile(user: $user, upload: UploadedFile::fake()->create('scan.pdf', 2))->execute();
        } finally {
            $this->assertEquals(0, File::query()->count());
        }
    }

    #[Test]
    public function it_never_meters_an_account_with_no_ceiling(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $user->account->update(['storage_limit_in_bytes' => null]);

        $file = new CreateFile(user: $user, upload: UploadedFile::fake()->create('scan.pdf', 500))->execute();

        $this->assertInstanceOf(File::class, $file);
    }

    #[Test]
    public function it_refuses_a_vault_in_another_account(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $elsewhere = Vault::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateFile(
            user: $user,
            upload: UploadedFile::fake()->create('scan.pdf', 1),
            vault: $elsewhere,
        )->execute();
    }

    #[Test]
    public function it_removes_the_stored_bytes_when_the_work_around_it_is_rolled_back(): void
    {
        Queue::fake();
        Storage::fake('local');

        $angela = User::factory()->create();

        try {
            DB::transaction(function () use ($angela): void {
                new CreateFile(user: $angela, upload: UploadedFile::fake()->create('party-planning.pdf', 4, 'application/pdf'))->execute();

                throw new RuntimeException('The party planning committee was dissolved.');
            });
        } catch (RuntimeException) {
            $this->assertSame([], Storage::disk('local')->allFiles());
        }
    }
}
