<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_saves_the_address(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->withConfirmedPassword()->actingAs($user)->put(route('settings.email.update'), [
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        $response->assertRedirect(route('settings.profile.index'));
        $response->assertSessionHas('status', 'Your email address is saved.');

        $this->assertEquals('michael.scott@dundermifflin.com', $user->refresh()->email);
    }

    #[Test]
    public function it_refuses_an_address_another_user_already_signs_in_with(): void
    {
        $user = User::factory()->create();
        $colleague = User::factory()->create(['email' => 'oscar.martinez@dundermifflin.com']);

        $response = $this->withConfirmedPassword()->actingAs($user)->put(route('settings.email.update'), [
            'email' => $colleague->email,
        ]);

        $response->assertSessionHasErrors('email');
    }

    #[Test]
    public function it_accepts_the_address_the_user_already_signs_in_with(): void
    {
        Queue::fake();

        $user = User::factory()->create(['email' => 'kelly.kapoor@dundermifflin.com']);

        $response = $this->withConfirmedPassword()->actingAs($user)->put(route('settings.email.update'), [
            'email' => 'kelly.kapoor@dundermifflin.com',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('settings.profile.index'));
    }
}
