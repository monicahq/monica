<?php

namespace Tests\Unit\Domains\Vault\ManageVaultSettings\Services;

use App\Domains\Vault\ManageVaultSettings\Services\GrantVaultAccessToUser;
use App\Exceptions\NotEnoughPermissionException;
use App\Exceptions\SameUserException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactReminder;
use App\Models\User;
use App\Models\UserNotificationChannel;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GrantVaultAccessToUserTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gives_the_right_to_access_the_vault_to_another_user(): void
    {
        $user = $this->createAdministrator();
        $anotherUser = User::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_MANAGE, $vault);
        $this->executeService($user->account, $user, $anotherUser, $vault);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = $this->createAdministrator();
        $account = Account::factory()->create();
        $anotherUser = User::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_MANAGE, $vault);
        $this->executeService($account, $user, $anotherUser, $vault);
    }

    /** @test */
    public function it_fails_if_the_other_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = $this->createAdministrator();
        $anotherUser = User::factory()->create();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_MANAGE, $vault);
        $this->executeService($user->account, $user, $anotherUser, $vault);
    }

    /** @test */
    public function it_fails_if_user_is_not_vault_manager(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $user = $this->createUser();
        $anotherUser = User::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $this->executeService($user->account, $user, $anotherUser, $vault);
    }

    /** @test */
    public function it_fails_if_user_and_the_other_user_are_the_same(): void
    {
        $this->expectException(SameUserException::class);

        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_MANAGE, $vault);
        $this->executeService($user->account, $user, $user, $vault);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new GrantVaultAccessToUser)->execute($request);
    }

    private function executeService(Account $account, User $user, User $anotherUser, Vault $vault): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));

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
            'day' => 2,
            'month' => 10,
            'year' => 1090,
            'number_times_triggered' => 0,
        ]);

        $request = [
            'account_id' => $account->id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
            'user_id' => $anotherUser->id,
            'permission' => Vault::PERMISSION_VIEW,
        ];

        $user = (new GrantVaultAccessToUser)->execute($request);

        $this->assertDatabaseCount('contacts', 3);

        $contact = Contact::firstWhere('first_name', $anotherUser->first_name);

        $this->assertFalse(
            $contact->can_be_deleted
        );
        $this->assertEquals(
            $anotherUser->name,
            $contact->first_name.' '.$contact->last_name
        );

        $this->assertInstanceOf(
            User::class,
            $user
        );

        $this->assertDatabaseHas('user_vault', [
            'vault_id' => $vault->id,
            'user_id' => $anotherUser->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertDatabaseHas('contact_reminder_scheduled', [
            'user_notification_channel_id' => $channel->id,
            'contact_reminder_id' => $contactReminder->id,
            'scheduled_at' => '2018-10-02 09:00:00',
        ]);
    }
}
