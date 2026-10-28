<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CancelAccountImport;
use App\Enums\AccountImportStatusEnum;
use App\Models\AccountImport;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CancelAccountImportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_discards_an_import_that_has_written_nothing_yet(): void
    {
        Storage::fake('local');
        $import = AccountImport::factory()->create(['status' => AccountImportStatusEnum::Read]);
        Storage::disk('local')->put($import->directory().'/upload.json', '{}');

        new CancelAccountImport(user: $import->user, import: $import)->execute();

        $this->assertModelMissing($import);
        Storage::disk('local')->assertMissing($import->directory());
    }

    #[Test]
    public function it_marks_an_import_that_is_writing_for_its_rollback_and_leaves_the_hand_over_to_the_run_writing_it(): void
    {
        Queue::fake();
        $import = AccountImport::factory()->create(['status' => AccountImportStatusEnum::Writing]);

        new CancelAccountImport(user: $import->user, import: $import)->execute();

        $this->assertSame(AccountImportStatusEnum::RollingBack, $import->refresh()->status);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_refuses_an_import_that_completed_after_the_screen_was_drawn(): void
    {
        $import = AccountImport::factory()->create(['status' => AccountImportStatusEnum::Writing]);
        AccountImport::query()->whereKey($import->id)->update(['status' => AccountImportStatusEnum::Completed->value]);

        try {
            new CancelAccountImport(user: $import->user, import: $import)->execute();
            $this->fail('The cancel went through.');
        } catch (InvalidArgumentException) {
            $this->assertSame(AccountImportStatusEnum::Completed, $import->refresh()->status);
        }
    }

    #[Test]
    public function it_refuses_an_import_that_has_ended(): void
    {
        $import = AccountImport::factory()->create(['status' => AccountImportStatusEnum::Completed]);

        $this->expectException(InvalidArgumentException::class);

        new CancelAccountImport(user: $import->user, import: $import)->execute();
    }

    #[Test]
    public function it_refuses_an_import_of_another_account(): void
    {
        $import = AccountImport::factory()->create(['status' => AccountImportStatusEnum::Read]);

        $this->expectException(ModelNotFoundException::class);

        new CancelAccountImport(user: User::factory()->create(), import: $import)->execute();
    }
}
