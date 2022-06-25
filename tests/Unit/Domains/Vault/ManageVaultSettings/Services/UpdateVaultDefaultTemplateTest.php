<?php

namespace Tests\Unit\Domains\Vault\ManageVaultSettings\Services;

use App\Exceptions\NotEnoughPermissionException;
use App\Jobs\CreateAuditLog;
use App\Models\Account;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Vault\ManageVaultSettings\Services\UpdateVaultDefaultTemplate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateVaultDefaultTemplateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_vault_default_template(): void
    {
        $ross = $this->createUser();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $template = Template::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $vault, $template);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateVaultDefaultTemplate())->execute($request);
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
        $template = Template::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $account, $vault, $template);
    }

    /** @test */
    public function it_fails_if_vault_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createUser();
        $vault = Vault::factory()->create();
        $template = Template::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $vault, $template);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_VIEW, $vault);
        $template = Template::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $vault, $template);
    }

    /** @test */
    public function it_fails_if_the_template_doesnt_belong_to_the_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createUser();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $template = Template::factory()->create();
        $this->executeService($ross, $ross->account, $vault, $template);
    }

    private function executeService(User $author, Account $account, Vault $vault, Template $template): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'vault_id' => $vault->id,
            'template_id' => $template->id,
        ];

        (new UpdateVaultDefaultTemplate())->execute($request);

        $this->assertDatabaseHas('vaults', [
            'id' => $vault->id,
            'account_id' => $account->id,
            'default_template_id' => $template->id,
        ]);

        Queue::assertPushed(CreateAuditLog::class, function ($job) {
            return $job->auditLog['action_name'] === 'vault_updated';
        });
    }
}
