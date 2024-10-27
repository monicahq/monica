<?php

namespace Tests\Unit\Domains\Vault\ManageVaultSettings\Services;

use App\Domains\Vault\ManageVaultSettings\Services\UpdateLabel;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Label;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateLabelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_label(): void
    {
        $ross = $this->createAdministrator();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $label = Label::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $this->executeService($ross, $ross->account, $vault, $label);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateLabel)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $label = Label::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $this->executeService($ross, $account, $vault, $label);
    }

    /** @test */
    public function it_fails_if_label_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $label = Label::factory()->create();
        $this->executeService($ross, $ross->account, $vault, $label);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_account(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_VIEW, $vault);
        $label = Label::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $this->executeService($ross, $ross->account, $vault, $label);
    }

    private function executeService(User $author, Account $account, Vault $vault, Label $label): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'vault_id' => $vault->id,
            'label_id' => $label->id,
            'name' => 'label name',
            'bg_color' => 'bg-zinc-700',
            'text_color' => 'bg-zinc-700',
        ];

        $label = (new UpdateLabel)->execute($request);

        $this->assertDatabaseHas('labels', [
            'id' => $label->id,
            'vault_id' => $vault->id,
            'name' => 'label name',
            'bg_color' => 'bg-zinc-700',
            'text_color' => 'bg-zinc-700',
        ]);
    }
}
