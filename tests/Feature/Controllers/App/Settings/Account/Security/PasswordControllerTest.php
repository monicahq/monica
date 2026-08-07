<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Security;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
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
            'password_hash' => Hash::make('beets-bears-battlestar'),
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

        $this->assertTrue(Hash::check('schrute-farms-2005', $user->refresh()->password_hash));

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
            'password_hash' => Hash::make('beets-bears-battlestar'),
        ]);

        $response = $this->actingAs($user)->put(route('settings.password.update'), [
            'current_password' => 'that-is-what-she-said',
            'new_password' => 'schrute-farms-2005',
            'new_password_confirmation' => 'schrute-farms-2005',
        ]);

        $response->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('beets-bears-battlestar', $user->refresh()->password_hash));
    }

    #[Test]
    public function it_refuses_a_new_password_that_is_not_confirmed(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('beets-bears-battlestar'),
        ]);

        $response = $this->actingAs($user)->put(route('settings.password.update'), [
            'current_password' => 'beets-bears-battlestar',
            'new_password' => 'schrute-farms-2005',
            'new_password_confirmation' => 'dunder-mifflin-2005',
        ]);

        $response->assertSessionHasErrors('new_password');

        $this->assertTrue(Hash::check('beets-bears-battlestar', $user->refresh()->password_hash));
    }

    #[Test]
    public function it_refuses_a_new_password_that_is_too_short(): void
    {
        $user = User::factory()->create([
            'password_hash' => Hash::make('beets-bears-battlestar'),
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
}
