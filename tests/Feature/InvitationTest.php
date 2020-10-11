<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\Account\Account;
use App\Models\Account\Invitation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class InvitationTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_it_can_open_invitation()
    {
        $account = factory(Account::class)->create();

        $invitation = factory(Invitation::class)->create([
            'account_id' => $account->id,
            'email' => 'test@test.com',
        ]);

        $response = $this->get('/invitations/accept/'.$invitation->invitation_key);

        $response->assertStatus(200);
        $response->assertSee('test@test.com');
    }

    public function test_it_can_respond_to_invitation()
    {
        NotificationFacade::fake();

        $account = factory(Account::class)->create();

        $invitation = factory(Invitation::class)->create([
            'account_id' => $account->id,
            'email' => 'test@test.com',
        ]);

        $response = $this->post('/invitations/accept/'.$invitation->invitation_key, [
            'email' => 'test@test007.com',
            'first_name' => 'john',
            'last_name' => 'doe',
            'password' => 'admin0',
            'password_confirmation' => 'admin0',
            'policy' => 'true',
            'email_security' => $invitation->invitedBy->email,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/dashboard');

        $this->assertDataBaseHas('users', [
            'email' => 'test@test007.com',
            'first_name' => 'john',
            'last_name' => 'doe',
            'invited_by_user_id' => $invitation->invitedBy->id,
        ]);
    }
}
