<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Enums\EmailType;
use App\Jobs\SendEmail;
use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MagicLinkControllerTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'aaaabbbbccccddddeeeeffffgggghhhhiiiijjjjkkkkllllmmmmnnnnoooopppp';

    #[Test]
    public function it_shows_the_page_that_asks_for_a_link(): void
    {
        $response = $this->get(route('auth.magicLink.new'));

        $response->assertStatus(200);
        $response->assertSee('Get a link to sign in');
    }

    #[Test]
    public function it_emails_a_link(): void
    {
        Queue::fake();

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->post(route('auth.magicLink.create'), [
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Check your inbox');

        Queue::assertPushedOn(
            queue: 'high',
            job: SendEmail::class,
            callback: fn (SendEmail $job): bool => $job->emailType === EmailType::MagicLinkCreated
                && $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_records_the_email_it_sends(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $this->post(route('auth.magicLink.create'), [
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        $this->assertDatabaseHas('emails_sent', [
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'email_type' => EmailType::MagicLinkCreated->value,
            'email_address' => 'michael.scott@dundermifflin.com',
        ]);
    }

    #[Test]
    public function it_says_the_same_thing_when_the_address_has_no_account(): void
    {
        Queue::fake();

        $response = $this->post(route('auth.magicLink.create'), [
            'email' => 'nobody@dundermifflin.com',
        ]);

        // Identical to the answer above, so this form cannot be used to find
        // out who has an account here.
        $response->assertStatus(200);
        $response->assertSee('Check your inbox');

        Queue::assertNotPushed(SendEmail::class);
    }

    #[Test]
    public function it_signs_a_user_in_through_the_link(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        MagicLink::factory()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', self::TOKEN),
        ]);

        $response = $this->get(route('auth.magicLink.show', ['token' => self::TOKEN]));

        $response->assertRedirect(route('home.index'));
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function it_sends_somebody_who_uses_two_factor_to_the_challenge(): void
    {
        Queue::fake();

        $user = User::factory()->twoFactor()->create();
        MagicLink::factory()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', self::TOKEN),
        ]);

        $response = $this->get(route('auth.magicLink.show', ['token' => self::TOKEN]));

        $response->assertRedirect(route('auth.twoFactor.new'));
        $response->assertSessionHas('twoFactor.user.id', $user->id);
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_link_that_was_already_used(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        MagicLink::factory()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', self::TOKEN),
        ]);

        $this->get(route('auth.magicLink.show', ['token' => self::TOKEN]));

        $this->post(route('auth.login.destroy'));

        $response = $this->get(route('auth.magicLink.show', ['token' => self::TOKEN]));

        $response->assertRedirect(route('auth.magicLink.new'));
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_link_that_ran_out_of_time(): void
    {
        Queue::fake();

        MagicLink::factory()->expired()->create([
            'user_id' => User::factory()->create()->id,
            'token' => hash('sha256', self::TOKEN),
        ]);

        $response = $this->get(route('auth.magicLink.show', ['token' => self::TOKEN]));

        $response->assertRedirect(route('auth.magicLink.new'));
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_token_it_has_never_seen(): void
    {
        $response = $this->get(route('auth.magicLink.show', ['token' => self::TOKEN]));

        $response->assertRedirect(route('auth.magicLink.new'));
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_disposable_address(): void
    {
        Queue::fake();

        $response = $this->post(route('auth.magicLink.create'), [
            'email' => 'michael.scott@mailinator.com',
        ]);

        $response->assertSessionHasErrors('email');
        Queue::assertNotPushed(SendEmail::class);
    }
}
