<?php

namespace Tests\Unit\Domains\Vault\ManageVaultSettings\Services;

use App\Exceptions\NotEnoughPermissionException;
use App\Jobs\CreateAuditLog;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactReminder;
use App\Models\User;
use App\Models\UserNotificationChannel;
use App\Models\Vault;
use App\Vault\ManageVaultSettings\Services\RemoveVaultAccess;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RemoveVaultAccessTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_removes_the_permission_in_the_vault(): void
    {
        $regis = $this->createAdministrator();
        $anotherUser = User::factory()->create([
            'account_id' => $regis->account_id,
        ]);
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_MANAGE, $vault);
        $contact = Contact::factory()->create();
        $vault->users()->save($anotherUser, [
            'permission' => Vault::PERMISSION_MANAGE,
            'contact_id' => $contact->id,
        ]);
        $this->executeService($regis->account, $regis, $anotherUser, $vault, $contact);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createAdministrator();
        $account = Account::factory()->create();
        $anotherUser = User::factory()->create([
            'account_id' => $regis->account_id,
        ]);
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_MANAGE, $vault);
        $contact = Contact::factory()->create();
        $vault->users()->save($anotherUser, [
            'permission' => Vault::PERMISSION_MANAGE,
            'contact_id' => $contact->id,
        ]);
        $this->executeService($account, $regis, $anotherUser, $vault, $contact);
    }

    /** @test */
    public function it_fails_if_the_other_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createAdministrator();
        $anotherUser = User::factory()->create();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_MANAGE, $vault);
        $contact = Contact::factory()->create();
        $vault->users()->save($anotherUser, [
            'permission' => Vault::PERMISSION_MANAGE,
            'contact_id' => $contact->id,
        ]);
        $this->executeService($regis->account, $regis, $anotherUser, $vault, $contact);
    }

    /** @test */
    public function it_fails_if_user_is_not_vault_manager(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $anotherUser = User::factory()->create([
            'account_id' => $regis->account_id,
        ]);
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $vault->users()->save($anotherUser, [
            'permission' => Vault::PERMISSION_MANAGE,
            'contact_id' => $contact->id,
        ]);
        $this->executeService($regis->account, $regis, $anotherUser, $vault, $contact);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new RemoveVaultAccess())->execute($request);
    }

    private function executeService(Account $account, User $regis, User $anotherUser, Vault $vault, Contact $contact): void
    {
        Queue::fake();

        // we'll create contact reminders so we can check that this new user
        // has also contact reminders scheduled too
        $channel = UserNotificationChannel::factory()->create([
            'user_id' => $anotherUser->id,
            'active' => false,
        ]);
        $otherContact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $contactReminder = ContactReminder::factory()->create([
            'contact_id' => $otherContact->id,
            'type' => ContactReminder::TYPE_ONE_TIME,
        ]);
        $contactReminder->userNotificationChannels()->sync([$channel->id => [
            'scheduled_at' => Carbon::now(),
        ]]);

        $request = [
            'account_id' => $account->id,
            'author_id' => $regis->id,
            'vault_id' => $vault->id,
            'user_id' => $anotherUser->id,
        ];

        (new RemoveVaultAccess())->execute($request);

        $this->assertDatabaseMissing('user_vault', [
            'vault_id' => $vault->id,
            'user_id' => $anotherUser->id,
            'permission' => Vault::PERMISSION_VIEW,
        ]);

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);

        $this->assertDatabaseMissing('contact_reminder_scheduled', [
            'user_notification_channel_id' => $channel->id,
            'contact_reminder_id' => $contactReminder->id,
        ]);

        Queue::assertPushed(CreateAuditLog::class, function ($job) {
            return $job->auditLog['action_name'] === 'vault_access_removed';
        });
    }
}
