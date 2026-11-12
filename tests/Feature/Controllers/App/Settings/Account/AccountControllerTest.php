<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_the_account_and_signs_the_person_out(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        $account = $user->account;

        $response = $this->withConfirmedPassword()->actingAs($user)->delete(route('settings.account.destroy'), [
            'confirm_email' => 'michael.scott@dundermifflin.com',
        ]);

        $response->assertRedirect(route('auth.login.new'));
        $response->assertSessionHas('status', 'Your account is deleted. Everything in it is gone.');

        $this->assertGuest();
        $this->assertModelMissing($account);
        $this->assertModelMissing($user);
    }

    #[Test]
    public function it_accepts_the_address_typed_in_another_case(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $this->withConfirmedPassword()->actingAs($user)->delete(route('settings.account.destroy'), [
            'confirm_email' => '  Michael.Scott@DunderMifflin.com  ',
        ]);

        $this->assertModelMissing($user);
    }

    #[Test]
    public function it_refuses_to_delete_the_account_when_the_address_does_not_match(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->withConfirmedPassword()->actingAs($user)->delete(route('settings.account.destroy'), [
            'confirm_email' => 'dwight.schrute@dundermifflin.com',
        ]);

        $response->assertSessionHasErrors('confirm_email');

        $this->assertAuthenticated();
        $this->assertModelExists($user);
    }

    #[Test]
    public function it_warns_that_deleting_the_account_stops_the_subscription(): void
    {
        $this->fakeStripe();

        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account);

        $this->actingAs($michael)->get(route('settings.profile.index'))
            ->assertSee('Your subscription stops the moment the account is gone.', escape: false);
    }

    #[Test]
    public function it_deletes_nothing_when_stripe_will_not_stop_the_subscription(): void
    {
        $client = $this->fakeStripe();
        $client->failOn('subscriptions*');

        $michael = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        $this->subscribeAccount($michael->account);

        $response = $this->withConfirmedPassword()->actingAs($michael)
            ->from(route('settings.profile.index'))
            ->delete(route('settings.account.destroy'), ['confirm_email' => 'michael.scott@dundermifflin.com']);

        $response->assertRedirect(route('settings.profile.index'));
        $response->assertSessionHasErrors('confirm_email');
        $this->assertModelExists($michael);
        $this->assertModelExists($michael->account);
    }
}
