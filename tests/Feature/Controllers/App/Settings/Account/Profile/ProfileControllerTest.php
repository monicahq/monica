<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_profile_screen(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
            'email' => 'pam.beesly@dundermifflin.com',
        ]);

        $response = $this->actingAs($user)->get(route('settings.profile.index'));

        $response->assertStatus(200);
        $response->assertSee('Pam', escape: false);
        $response->assertSee('Beesly', escape: false);
        $response->assertSee('pam.beesly@dundermifflin.com', escape: false);
    }

    #[Test]
    public function it_shows_the_toast_of_the_previous_save(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession([
                'status' => 'Your details are saved.',
                'status_description' => 'They appear everywhere your name does.',
            ])
            ->get(route('settings.profile.index'));

        $response->assertStatus(200);
        $response->assertSee('id="notifications"', escape: false);
        $response->assertSee('Your details are saved.', escape: false);
        $response->assertSee('They appear everywhere your name does.', escape: false);
    }

    #[Test]
    public function it_updates_the_details(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('settings.profile.update'), [
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);

        $response->assertRedirect(route('settings.profile.index'));
        $response->assertSessionHas('status');
        $response->assertSessionHas('status_description');

        $user->refresh();

        $this->assertEquals('Michael', $user->first_name);
        $this->assertEquals('Scott', $user->last_name);
    }

    #[Test]
    public function it_refuses_details_without_a_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('settings.profile.update'), [
            'first_name' => '',
            'last_name' => '',
        ]);

        $response->assertSessionHasErrors(['first_name', 'last_name']);
    }
}
