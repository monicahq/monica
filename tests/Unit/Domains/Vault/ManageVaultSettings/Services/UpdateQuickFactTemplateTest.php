<?php

namespace Tests\Unit\Domains\Vault\ManageVaultSettings\Services;

use App\Domains\Vault\ManageVaultSettings\Services\UpdateQuickFactTemplate;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultQuickFactsTemplate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateQuickFactTemplateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_quick_fact_template_entry(): void
    {
        $ross = $this->createAdministrator();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $quickFactTemplateEntry = VaultQuickFactsTemplate::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $this->executeService($ross, $ross->account, $vault, $quickFactTemplateEntry);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateQuickFactTemplate)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $quickFactTemplateEntry = VaultQuickFactsTemplate::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $this->executeService($ross, $account, $vault, $quickFactTemplateEntry);
    }

    /** @test */
    public function it_fails_if_quick_fact_template_entry_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $quickFactTemplateEntry = VaultQuickFactsTemplate::factory()->create();
        $this->executeService($ross, $ross->account, $vault, $quickFactTemplateEntry);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_account(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_VIEW, $vault);
        $quickFactTemplateEntry = VaultQuickFactsTemplate::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $this->executeService($ross, $ross->account, $vault, $quickFactTemplateEntry);
    }

    private function executeService(User $author, Account $account, Vault $vault, VaultQuickFactsTemplate $quickFactTemplateEntry): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'vault_id' => $vault->id,
            'vault_quick_facts_template_id' => $quickFactTemplateEntry->id,
            'label' => 'label name',
        ];

        $quickFactTemplateEntry = (new UpdateQuickFactTemplate)->execute($request);

        $this->assertDatabaseHas('vault_quick_facts_templates', [
            'id' => $quickFactTemplateEntry->id,
            'vault_id' => $vault->id,
            'label' => 'label name',
        ]);
    }
}
