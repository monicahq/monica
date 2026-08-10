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
    public function it_points_to_the_logs_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.profile.index'));

        $response->assertStatus(200);
        $response->assertSee(route('settings.logs.index'), escape: false);
    }

    #[Test]
    public function it_points_back_to_the_vaults(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.profile.index'));

        $response->assertStatus(200);
        $response->assertSee(route('vaults.index'), escape: false);
        $response->assertSee('Back to your vaults', escape: false);
    }

    #[Test]
    public function it_groups_the_last_entries_under_the_personalization_heading(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.profile.index'));

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            'Your account',
            'Personalization',
            route('settings.valueLists.index'),
            route('settings.entityTypes.index'),
            route('settings.templates.index'),
        ], escape: false);
    }

    #[Test]
    public function it_redirects_a_visitor_who_is_not_signed_in(): void
    {
        $response = $this->get(route('settings.profile.index'));

        $response->assertRedirect(route('auth.login.new'));
    }

    #[Test]
    public function it_updates_the_details(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('settings.profile.update'), [
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        $response->assertRedirect(route('settings.profile.index'));
        $response->assertSessionHas('status', 'Your details are saved.');
        $response->assertSessionHas('status_description', 'They appear everywhere your name does.');

        $user->refresh();

        $this->assertEquals('Michael', $user->first_name);
        $this->assertEquals('Scott', $user->last_name);
        $this->assertEquals('michael.scott@dundermifflin.com', $user->email);
    }

    #[Test]
    public function it_refuses_details_without_a_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('settings.profile.update'), [
            'first_name' => '',
            'last_name' => '',
            'email' => $user->email,
        ]);

        $response->assertSessionHasErrors(['first_name', 'last_name']);
    }

    #[Test]
    public function it_refuses_an_email_that_is_not_an_email(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('settings.profile.update'), [
            'first_name' => 'Angela',
            'last_name' => 'Martin',
            'email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors('email');
    }

    #[Test]
    public function it_refuses_an_email_another_user_already_signs_in_with(): void
    {
        $user = User::factory()->create();
        $colleague = User::factory()->create(['email' => 'oscar.martinez@dundermifflin.com']);

        $response = $this->actingAs($user)->put(route('settings.profile.update'), [
            'first_name' => 'Oscar',
            'last_name' => 'Martinez',
            'email' => $colleague->email,
        ]);

        $response->assertSessionHasErrors('email');
    }

    #[Test]
    public function it_accepts_the_email_the_user_already_signs_in_with(): void
    {
        Queue::fake();

        $user = User::factory()->create(['email' => 'kelly.kapoor@dundermifflin.com']);

        $response = $this->actingAs($user)->put(route('settings.profile.update'), [
            'first_name' => 'Kelly',
            'last_name' => 'Kapoor',
            'email' => 'kelly.kapoor@dundermifflin.com',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('settings.profile.index'));
    }
}
