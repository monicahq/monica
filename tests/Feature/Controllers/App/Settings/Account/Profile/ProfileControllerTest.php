<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Profile;

use App\Actions\UpdateUserPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
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
    public function it_points_back_to_the_settings(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.profile.index'));

        $response->assertStatus(200);
        $response->assertSee(route('settings.index'), escape: false);
        $response->assertSee('Back to settings', escape: false);
    }

    #[Test]
    public function it_carries_the_dock(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.profile.index'));

        $response->assertStatus(200);
        $response->assertSee('aria-label="Main"', escape: false);
        $response->assertSee(route('vaults.index'), escape: false);
    }

    #[Test]
    public function it_offers_the_photo_and_the_details_as_two_separate_forms(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.profile.index'));

        $response->assertStatus(200);
        $response->assertSee(route('settings.photo.update'), escape: false);
        $response->assertSee(route('settings.profile.update'), escape: false);
        $response->assertSee('id="photo-form"', escape: false);
        $response->assertSee('id="details-form"', escape: false);
    }

    #[Test]
    public function it_only_offers_removing_a_photo_when_there_is_one(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.profile.index'))
            ->assertDontSee('id="photo-delete-form"', escape: false);
    }

    /**
     * The button says what it does before Alpine has run, since a directive
     * written inside an attribute of a component tag is not compiled and would
     * leave it blank.
     */
    #[Test]
    public function the_button_that_removes_the_photo_is_worded(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();

        new UpdateUserPhoto(
            user: $user,
            file: UploadedFile::fake()->image('michael.jpg', 400, 400),
        )->execute();

        $this->actingAs($user->fresh())
            ->get(route('settings.profile.index'))
            ->assertSee('id="photo-delete-form"', escape: false)
            ->assertSee('x-text="confirming ? confirmLabel : removeLabel"', escape: false)
            ->assertSee("removeLabel: 'Remove the photo'", escape: false)
            ->assertDontSee('@js(', escape: false);
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
