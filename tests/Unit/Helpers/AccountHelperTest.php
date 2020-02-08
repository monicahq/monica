<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use function Safe\json_decode;
use App\Helpers\AccountHelper;
use App\Models\Account\Account;
use App\Models\Account\Invitation;
use App\Models\Contact\Contact;
use App\Models\User\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AccountHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function user_can_downgrade_with_only_one_user_and_no_pending_invitations_and_under_contact_limit(): void
    {
        config(['monica.number_of_allowed_contacts_free_account' => 1]);
        $contact = factory(Contact::class)->create();

        factory(User::class)->create([
            'account_id' => $contact->account->id,
        ]);

        $this->assertEquals(
            true,
            AccountHelper::canDowngrade($contact->account)
        );
    }

    /** @test */
    public function user_cant_downgrade_with_two_users(): void
    {
        $contact = factory(Contact::class)->create();

        factory(User::class, 3)->create([
            'account_id' => $contact->account->id,
        ]);

        $this->assertEquals(
            false,
            AccountHelper::canDowngrade($contact->account)
        );
    }

    /** @test */
    public function user_cant_downgrade_with_pending_invitations(): void
    {
        $account = factory(Account::class)->create();

        factory(Invitation::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertEquals(
            false,
            AccountHelper::canDowngrade($account)
        );
    }

    /** @test */
    public function user_cant_downgrade_with_too_many_contacts(): void
    {
        config(['monica.number_of_allowed_contacts_free_account' => 1]);
        $account = factory(Account::class)->create();

        factory(Contact::class, 2)->create([
            'account_id' => $account->id,
        ]);

        $this->assertFalse(
            AccountHelper::canDowngrade($account)
        );
    }
}
