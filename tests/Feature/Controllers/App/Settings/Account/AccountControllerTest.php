<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account;

use App\Models\Account;
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

        $response = $this->actingAs($user)->delete(route('settings.account.destroy'), [
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

        $this->actingAs($user)->delete(route('settings.account.destroy'), [
            'confirm_email' => '  Michael.Scott@DunderMifflin.com  ',
        ]);

        $this->assertModelMissing($user);
    }

    #[Test]
    public function it_refuses_to_delete_the_account_when_the_address_does_not_match(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->actingAs($user)->delete(route('settings.account.destroy'), [
            'confirm_email' => 'dwight.schrute@dundermifflin.com',
        ]);

        $response->assertSessionHasErrors('confirm_email');

        $this->assertAuthenticated();
        $this->assertModelExists($user);
    }

    #[Test]
    public function it_refuses_to_delete_the_account_when_nothing_was_typed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete(route('settings.account.destroy'));

        $response->assertSessionHasErrors('confirm_email');

        $this->assertModelExists($user);
    }

    #[Test]
    public function it_leaves_the_account_of_somebody_else_alone(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        $stranger = User::factory()->create();
        $strangerAccount = $stranger->account;

        $this->actingAs($user)->delete(route('settings.account.destroy'), [
            'confirm_email' => 'michael.scott@dundermifflin.com',
        ]);

        $this->assertModelExists($stranger);
        $this->assertModelExists($strangerAccount);
    }

    #[Test]
    public function it_shows_the_way_to_delete_the_account_on_the_profile_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.profile.index'));

        $response->assertStatus(200);
        $response->assertSee('Delete your account', escape: false);
        $response->assertSee('none of it can be brought back', escape: false);
        $response->assertSee(route('settings.account.destroy'), escape: false);
    }

    #[Test]
    public function it_redirects_a_visitor_who_is_not_signed_in(): void
    {
        $account = Account::factory()->create();

        $response = $this->delete(route('settings.account.destroy'));

        $response->assertRedirect(route('auth.login.new'));

        $this->assertModelExists($account);
    }
}
