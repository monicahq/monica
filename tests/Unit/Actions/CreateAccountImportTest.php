<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateAccountImport;
use App\Enums\AccountImportStatusEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Jobs\ReadAccountImport;
use App\Models\AccountImport;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class CreateAccountImportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_keeps_the_file_under_the_account_and_has_it_read(): void
    {
        Storage::fake('local');
        Queue::fake();

        $michael = User::factory()->create();
        $contents = (string) file_get_contents(base_path('tests/Fixtures/Imports/monica-4-small.json'));

        $import = new CreateAccountImport(
            user: $michael,
            file: UploadedFile::fake()->createWithContent('monica.json', $contents),
        )->execute();

        $this->assertInstanceOf(AccountImport::class, $import);
        $this->assertSame('accounts/'.$michael->account_id.'/transfers/'.$import->uuid.'/upload.json', $import->path);
        Storage::disk('local')->assertExists($import->path);

        $this->assertDatabaseHas('account_imports', [
            'id' => $import->id,
            'account_id' => $michael->account_id,
            'user_id' => $michael->id,
            'status' => AccountImportStatusEnum::Reading->value,
            'source_checksum' => hash('sha256', $contents),
        ]);

        Queue::assertPushed(ReadAccountImport::class, fn (ReadAccountImport $job): bool => $job->import->is($import));
        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::AccountImportStarted
                && $job->account->id === $michael->account_id
                && $job->user->id === $michael->id,
        );
    }

    #[Test]
    public function it_removes_the_upload_when_the_work_around_it_is_rolled_back(): void
    {
        Storage::fake('local');
        Queue::fake();

        $michael = User::factory()->create();

        try {
            DB::transaction(function () use ($michael): void {
                new CreateAccountImport(
                    user: $michael,
                    file: UploadedFile::fake()->createWithContent('monica.json', '{"version": "1.0-preview.1"}'),
                )->execute();

                throw new RuntimeException('Dunder Mifflin was sold.');
            });
        } catch (RuntimeException) {
            $this->assertSame([], Storage::disk('local')->allFiles());
        }
    }

    #[Test]
    public function it_refuses_a_second_import_while_the_first_runs(): void
    {
        Storage::fake('local');
        Queue::fake();

        $michael = User::factory()->create();
        AccountImport::factory()->create(['user_id' => $michael->id, 'status' => AccountImportStatusEnum::Writing]);

        $this->expectException(AuthorizationException::class);

        new CreateAccountImport(
            user: $michael,
            file: UploadedFile::fake()->createWithContent('monica.json', '{}'),
        )->execute();
    }
}
