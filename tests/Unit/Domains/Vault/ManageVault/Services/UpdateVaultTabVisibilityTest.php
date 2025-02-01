<?php

namespace Tests\Unit\Domains\Vault\ManageVault\Services;

use App\Domains\Vault\ManageVault\Services\UpdateVaultTabVisibility;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateVaultTabVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_vault(): void
    {
        $ross = $this->createUser();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $this->executeService($ross, $ross->account, $vault);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateVaultTabVisibility)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createUser();
        $account = Account::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $account, $vault);
    }

    /** @test */
    public function it_fails_if_vault_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createUser();
        $vault = Vault::factory()->create();
        $this->executeService($ross, $ross->account, $vault);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_VIEW, $vault);
        $this->executeService($ross, $ross->account, $vault);
    }

    private function executeService(User $author, Account $account, Vault $vault): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'vault_id' => $vault->id,
            'show_group_tab' => true,
            'show_tasks_tab' => true,
            'show_files_tab' => true,
            'show_journal_tab' => true,
            'show_companies_tab' => true,
            'show_reports_tab' => true,
            'show_calendar_tab' => true,
        ];

        $vault = (new UpdateVaultTabVisibility)->execute($request);

        $this->assertDatabaseHas('vaults', [
            'id' => $vault->id,
            'account_id' => $account->id,
            'show_group_tab' => true,
            'show_tasks_tab' => true,
            'show_files_tab' => true,
            'show_journal_tab' => true,
            'show_companies_tab' => true,
            'show_reports_tab' => true,
            'show_calendar_tab' => true,
        ]);
    }
}
