<?php

namespace Tests\Unit\Domains\Vault\ManageVaultImportantDateTypes\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vault;
use App\Models\Account;
use App\Jobs\CreateAuditLog;
use Illuminate\Support\Facades\Queue;
use App\Models\ContactImportantDateType;
use Illuminate\Validation\ValidationException;
use App\Exceptions\NotEnoughPermissionException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Vault\ManageVaultImportantDateTypes\Services\UpdateContactImportantDateType;

class UpdateContactImportantDateTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_type(): void
    {
        $ross = $this->createAdministrator();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $type = ContactImportantDateType::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $this->executeService($ross, $ross->account, $vault, $type);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateContactImportantDateType)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $type = ContactImportantDateType::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $this->executeService($ross, $account, $vault, $type);
    }

    /** @test */
    public function it_fails_if_type_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $type = ContactImportantDateType::factory()->create();
        $this->executeService($ross, $ross->account, $vault, $type);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_account(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_VIEW, $vault);
        $type = ContactImportantDateType::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $this->executeService($ross, $ross->account, $vault, $type);
    }

    private function executeService(User $author, Account $account, Vault $vault, ContactImportantDateType $type): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'vault_id' => $vault->id,
            'contact_important_date_type_id' => $type->id,
            'label' => 'label name',
            'can_be_deleted' => false,
        ];

        $type = (new UpdateContactImportantDateType)->execute($request);

        $this->assertDatabaseHas('contact_important_date_types', [
            'id' => $type->id,
            'vault_id' => $vault->id,
            'label' => 'label name',
            'can_be_deleted' => false,
        ]);

        Queue::assertPushed(CreateAuditLog::class, function ($job) {
            return $job->auditLog['action_name'] === 'contact_important_date_type_updated';
        });
    }
}
