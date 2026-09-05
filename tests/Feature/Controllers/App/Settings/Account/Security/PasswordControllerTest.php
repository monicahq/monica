<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Security;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Passkey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_the_password(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'password' => Hash::make('beets-bears-battlestar'),
            'password_changed_at' => null,
        ]);

        $response = $this->actingAs($user)->put(route('settings.password.update'), [
            'current_password' => 'beets-bears-battlestar',
            'new_password' => 'schrute-farms-2005',
            'new_password_confirmation' => 'schrute-farms-2005',
        ]);

        $response->assertRedirect(route('settings.security.index'));
        $response->assertSessionHas('status', 'Your password is changed.');
        $response->assertSessionHas('status_description', 'Use it the next time you sign in.');

        $this->assertTrue(Hash::check('schrute-farms-2005', $user->refresh()->password));

        $this->assertEqualsWithDelta(
            now()->timestamp,
            $user->refresh()->password_changed_at?->timestamp,
            2,
        );

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserPasswordUpdate
                && $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_refuses_a_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('beets-bears-battlestar'),
        ]);

        $response = $this->actingAs($user)->put(route('settings.password.update'), [
            'current_password' => 'that-is-what-she-said',
            'new_password' => 'schrute-farms-2005',
            'new_password_confirmation' => 'schrute-farms-2005',
        ]);

        $response->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('beets-bears-battlestar', $user->refresh()->password));
    }

    #[Test]
    public function it_refuses_a_new_password_that_is_not_confirmed(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('beets-bears-battlestar'),
        ]);

        $response = $this->actingAs($user)->put(route('settings.password.update'), [
            'current_password' => 'beets-bears-battlestar',
            'new_password' => 'schrute-farms-2005',
            'new_password_confirmation' => 'dunder-mifflin-2005',
        ]);

        $response->assertSessionHasErrors('new_password');

        $this->assertTrue(Hash::check('beets-bears-battlestar', $user->refresh()->password));
    }

    #[Test]
    public function it_refuses_a_new_password_that_is_too_short(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('beets-bears-battlestar'),
        ]);

        $response = $this->actingAs($user)->put(route('settings.password.update'), [
            'current_password' => 'beets-bears-battlestar',
            'new_password' => 'beets',
            'new_password_confirmation' => 'beets',
        ]);

        $response->assertSessionHasErrors('new_password');
    }

    #[Test]
    public function it_redirects_a_visitor_who_is_not_signed_in(): void
    {
        $response = $this->put(route('settings.password.update'), []);

        $response->assertRedirect(route('auth.login.new'));
    }

    #[Test]
    public function it_removes_the_password_of_somebody_who_holds_a_passkey(): void
    {
        Queue::fake();
        URL::forceScheme('https');

        $user = User::factory()->create();
        Passkey::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('settings.password.destroy'));

        $response->assertRedirect(route('settings.security.index'));
        $response->assertSessionHas('status');
        $this->assertNull($user->refresh()->password);
    }

    #[Test]
    public function it_refuses_to_remove_the_password_of_somebody_with_no_passkey(): void
    {
        Queue::fake();
        URL::forceScheme('https');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete(route('settings.password.destroy'));

        $response->assertSessionHasErrors('password');
        $this->assertNotNull($user->refresh()->password);
    }

    #[Test]
    public function it_refuses_to_remove_the_password_where_a_passkey_could_not_be_used(): void
    {
        Queue::fake();

        URL::forceScheme('http');

        $user = User::factory()->create();
        Passkey::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('settings.password.destroy'));

        $response->assertSessionHasErrors('password');
        $this->assertNotNull($user->refresh()->password);
    }

    #[Test]
    public function it_refuses_to_remove_the_password_of_a_single_sign_on_account(): void
    {
        Queue::fake();
        URL::forceScheme('https');

        $user = User::factory()->singleSignOn()->create();

        $this->actingAs($user)->delete(route('settings.password.destroy'))
            ->assertSessionHasErrors('password');
    }

    #[Test]
    public function it_sets_a_password_on_an_account_that_has_none(): void
    {
        Queue::fake();

        $user = User::factory()->create(['password' => null]);

        $response = $this->actingAs($user)->post(route('settings.password.create'), [
            'new_password' => 'thatswhatshesaid',
            'new_password_confirmation' => 'thatswhatshesaid',
        ]);

        $response->assertRedirect(route('settings.security.index'));
        $this->assertNotNull($user->refresh()->password);
        $this->assertNotNull($user->refresh()->password_changed_at);
    }

    #[Test]
    public function it_refuses_to_set_a_password_where_one_already_exists(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.password.create'), [
            'new_password' => 'thatswhatshesaid',
            'new_password_confirmation' => 'thatswhatshesaid',
        ])->assertSessionHasErrors('new_password');
    }

    #[Test]
    public function it_requires_a_confirmation_and_a_length_when_setting_a_password(): void
    {
        Queue::fake();

        $user = User::factory()->create(['password' => null]);

        $this->actingAs($user)->post(route('settings.password.create'), [
            'new_password' => 'thatswhatshesaid',
            'new_password_confirmation' => 'somethingelse',
        ])->assertSessionHasErrors('new_password');

        $this->actingAs($user)->post(route('settings.password.create'), [
            'new_password' => 'short',
            'new_password_confirmation' => 'short',
        ])->assertSessionHasErrors('new_password');

        $this->assertNull($user->refresh()->password);
    }

    #[Test]
    public function it_refuses_a_guest_on_both_new_routes(): void
    {
        $this->post(route('settings.password.create'), [])->assertRedirect(route('auth.login.new'));
        $this->delete(route('settings.password.destroy'))->assertRedirect(route('auth.login.new'));
    }
}
