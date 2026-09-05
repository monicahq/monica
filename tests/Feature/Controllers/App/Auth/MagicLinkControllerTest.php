<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Enums\EmailType;
use App\Models\MagicLink;
use App\Models\User;
use App\Notifications\MagicLinkCreated;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
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
        Notification::fake();

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->post(route('auth.magicLink.create'), [
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Check your inbox');

        Notification::assertSentTo(
            $user,
            MagicLinkCreated::class,
            fn (MagicLinkCreated $notification): bool => $notification->queue === 'high',
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
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'email_type' => EmailType::MagicLinkCreated->value,
            'email_address' => 'michael.scott@dundermifflin.com',
        ]);
    }

    #[Test]
    public function it_says_the_same_thing_when_the_address_has_no_account(): void
    {
        Notification::fake();

        $response = $this->post(route('auth.magicLink.create'), [
            'email' => 'nobody@dundermifflin.com',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Check your inbox');

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_signs_a_user_in_through_the_link(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $link = $this->linkFor($user);

        $response = $this->get($this->signedUrlFor($link));

        $response->assertRedirect(route('home.index'));
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function it_sends_somebody_who_uses_two_factor_to_the_challenge(): void
    {
        Queue::fake();

        $user = User::factory()->twoFactor()->create();
        $link = $this->linkFor($user);

        $response = $this->get($this->signedUrlFor($link));

        $response->assertRedirect(route('auth.twoFactor.new'));
        $response->assertSessionHas('twoFactor.user.id', $user->id);
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_link_that_was_already_used(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $link = $this->linkFor($user);
        $url = $this->signedUrlFor($link);

        $this->get($url);

        $this->post(route('auth.login.destroy'));

        $response = $this->get($url);

        $response->assertRedirect(route('auth.magicLink.new'));
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_link_that_ran_out_of_time_even_where_the_signature_still_stands(): void
    {
        Queue::fake();

        $link = $this->linkFor(User::factory()->create(), ['expires_at' => now()->subMinute()]);

        $response = $this->get($this->signedUrlFor($link, now()->addMinutes(5)));

        $response->assertRedirect(route('auth.magicLink.new'));
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_link_whose_signature_ran_out(): void
    {
        Queue::fake();

        $link = $this->linkFor(User::factory()->create());

        $response = $this->get($this->signedUrlFor($link, now()->subMinute()));

        $response->assertForbidden();
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_link_whose_signature_was_tampered_with(): void
    {
        Queue::fake();

        $link = $this->linkFor(User::factory()->create());

        $response = $this->get($this->signedUrlFor($link).'&intended=home');

        $response->assertForbidden();
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_token_that_does_not_match_the_link_it_names(): void
    {
        Queue::fake();

        $link = $this->linkFor(User::factory()->create());

        $response = $this->get(URL::temporarySignedRoute(
            'auth.magicLink.show',
            now()->addMinutes(5),
            ['token' => str_repeat('z', 64)],
        ));

        $response->assertRedirect(route('auth.magicLink.new'));
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_link_it_has_never_seen(): void
    {
        $response = $this->get(URL::temporarySignedRoute(
            'auth.magicLink.show',
            now()->addMinutes(5),
            ['token' => self::TOKEN],
        ));

        $response->assertRedirect(route('auth.magicLink.new'));
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_disposable_address(): void
    {
        Notification::fake();

        $response = $this->post(route('auth.magicLink.create'), [
            'email' => 'michael.scott@mailinator.com',
        ]);

        $response->assertSessionHasErrors('email');
        Notification::assertNothingSent();
    }

    private function linkFor(User $user, array $overrides = []): MagicLink
    {
        return MagicLink::factory()->create(array_merge([
            'user_id' => $user->id,
            'token' => hash('sha256', self::TOKEN),
        ], $overrides));
    }

    private function signedUrlFor(MagicLink $link, ?CarbonInterface $expiration = null): string
    {
        return URL::temporarySignedRoute(
            'auth.magicLink.show',
            $expiration ?? $link->expires_at,
            [
                'token' => self::TOKEN,
            ],
        );
    }
}
