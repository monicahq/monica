<?php

namespace Tests\Unit\Domains\Vault\ManageVaultSettings\Services;

use App\Domains\Vault\ManageVaultSettings\Services\UpdateQuickFactTemplatePosition;
use App\Models\Account;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultQuickFactsTemplate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateQuickFactTemplatePositionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_quick_fact_template_position(): void
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
        (new UpdateQuickFactTemplatePosition)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $quickFactTemplateEntry = VaultQuickFactsTemplate::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $this->executeService($ross, $account, $vault, $quickFactTemplateEntry);
    }

    /** @test */
    public function it_fails_if_parameter_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $quickFactTemplateEntry = VaultQuickFactsTemplate::factory()->create();
        $this->executeService($ross, $ross->account, $vault, $quickFactTemplateEntry);
    }

    private function executeService(User $author, Account $account, Vault $vault, VaultQuickFactsTemplate $quickFactTemplateEntry): void
    {
        $quickFactTemplateEntry1 = VaultQuickFactsTemplate::factory()->create([
            'vault_id' => $vault->id,
            'position' => 1,
        ]);
        $quickFactTemplateEntry3 = VaultQuickFactsTemplate::factory()->create([
            'vault_id' => $vault->id,
            'position' => 3,
        ]);
        $quickFactTemplateEntry4 = VaultQuickFactsTemplate::factory()->create([
            'vault_id' => $vault->id,
            'position' => 4,
        ]);

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'vault_quick_facts_template_id' => $quickFactTemplateEntry->id,
            'new_position' => 3,
        ];

        $quickFactTemplateEntry = (new UpdateQuickFactTemplatePosition)->execute($request);

        $this->assertDatabaseHas('vault_quick_facts_templates', [
            'id' => $quickFactTemplateEntry1->id,
            'vault_id' => $vault->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('vault_quick_facts_templates', [
            'id' => $quickFactTemplateEntry3->id,
            'vault_id' => $vault->id,
            'position' => 2,
        ]);
        $this->assertDatabaseHas('vault_quick_facts_templates', [
            'id' => $quickFactTemplateEntry4->id,
            'vault_id' => $vault->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('vault_quick_facts_templates', [
            'id' => $quickFactTemplateEntry->id,
            'vault_id' => $vault->id,
            'position' => 3,
        ]);

        $request['new_position'] = 2;

        $quickFactTemplateEntry = (new UpdateQuickFactTemplatePosition)->execute($request);

        $this->assertDatabaseHas('vault_quick_facts_templates', [
            'id' => $quickFactTemplateEntry1->id,
            'vault_id' => $vault->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('vault_quick_facts_templates', [
            'id' => $quickFactTemplateEntry3->id,
            'vault_id' => $vault->id,
            'position' => 3,
        ]);
        $this->assertDatabaseHas('vault_quick_facts_templates', [
            'id' => $quickFactTemplateEntry4->id,
            'vault_id' => $vault->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('vault_quick_facts_templates', [
            'id' => $quickFactTemplateEntry->id,
            'vault_id' => $vault->id,
            'position' => 2,
        ]);

        $this->assertInstanceOf(
            VaultQuickFactsTemplate::class,
            $quickFactTemplateEntry
        );
    }
}
