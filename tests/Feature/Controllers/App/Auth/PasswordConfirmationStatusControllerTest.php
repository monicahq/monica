<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordConfirmationStatusControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_the_password_is_confirmed_within_the_window(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withConfirmedPassword()
            ->get(route('auth.passwordConfirmationStatus.show'));

        $response->assertOk();
        $response->assertExactJson(['confirmed' => true]);
    }

    #[Test]
    public function it_says_the_password_is_not_confirmed_when_nothing_was_confirmed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('auth.passwordConfirmationStatus.show'));

        $response->assertOk();
        $response->assertExactJson(['confirmed' => false]);
    }

    #[Test]
    public function it_says_the_password_is_not_confirmed_once_the_window_has_passed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->withConfirmedPassword();

        $this->travel((int) config('auth.password_timeout') + 1)->seconds();

        $response = $this->get(route('auth.passwordConfirmationStatus.show'));

        $response->assertOk();
        $response->assertExactJson(['confirmed' => false]);
    }

    #[Test]
    public function asking_does_not_use_up_the_password_attempts(): void
    {
        $user = User::factory()->create(['password' => Hash::make('dundermifflin')]);

        foreach (range(1, 10) as $ignored) {
            $this->actingAs($user)->get(route('auth.passwordConfirmationStatus.show'))->assertOk();
        }

        $this->actingAs($user)->post(route('auth.confirmPassword.create'), ['password' => 'dundermifflin'])
            ->assertRedirect(route('settings.security.index'));
    }
}
