<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateAccountExport;
use App\Enums\AccountExportStatusEnum;
use App\Enums\UserActionEnum;
use App\Jobs\BuildAccountExport;
use App\Jobs\LogUserAction;
use App\Models\AccountExport;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateAccountExportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_asks_for_every_vault_of_the_account_and_has_the_file_built(): void
    {
        Queue::fake();
        $this->travelTo('2026-10-18 09:00:00');

        $michael = User::factory()->create();
        $scranton = Vault::factory()->create(['account_id' => $michael->account_id, 'position' => 1]);
        $nashua = Vault::factory()->create(['account_id' => $michael->account_id, 'position' => 2]);
        Vault::factory()->create();

        $export = new CreateAccountExport(user: $michael)->execute();

        $this->assertInstanceOf(AccountExport::class, $export);
        $this->assertSame([$scranton->id, $nashua->id], $export->refresh()->vault_ids);
        $this->assertSame("accounts/{$michael->account_id}/transfers/{$export->uuid}/monica-export-2026-10-18.json", $export->path);
        $this->assertDatabaseHas('account_exports', [
            'id' => $export->id,
            'account_id' => $michael->account_id,
            'user_id' => $michael->id,
            'status' => AccountExportStatusEnum::Preparing->value,
            'disk' => config('filesystems.default'),
        ]);

        Queue::assertPushed(BuildAccountExport::class, fn (BuildAccountExport $job): bool => $job->export->is($export));
        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::AccountExportRequested
                && $job->user->id === $michael->id
                && $job->parameters === ['count' => 2],
        );
    }

    #[Test]
    public function it_covers_only_the_vaults_chosen(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $scranton = Vault::factory()->create(['account_id' => $michael->account_id]);
        Vault::factory()->create(['account_id' => $michael->account_id]);

        $export = new CreateAccountExport(user: $michael, vaultIds: [$scranton->id])->execute();

        $this->assertSame([$scranton->id], $export->refresh()->vault_ids);
    }

    #[Test]
    public function it_refuses_a_vault_of_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $stamford = Vault::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateAccountExport(user: $michael, vaultIds: [$stamford->id])->execute();
    }

    #[Test]
    public function it_refuses_a_second_export_while_the_first_is_being_prepared(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        AccountExport::factory()->create(['user_id' => $michael->id, 'status' => AccountExportStatusEnum::Preparing]);

        $this->expectException(AuthorizationException::class);

        new CreateAccountExport(user: $michael)->execute();
    }
}
