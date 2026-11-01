<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Models\User;
use App\Notifications\PasswordConfirmationRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordConfirmationLinkControllerTest extends TestCase
{
    use RefreshDatabase;

    private function linkFor(User $user, int $minutes = 5): string
    {
        return URL::temporarySignedRoute(
            'auth.passwordConfirmationLink.show',
            now()->addMinutes($minutes),
            ['id' => $user->id],
        );
    }

    #[Test]
    public function it_sends_the_link_and_says_so(): void
    {
        Notification::fake();

        $dwight = User::factory()->create(['password' => null]);

        $response = $this->actingAs($dwight)
            ->from(route('auth.confirmPassword.new'))
            ->post(route('auth.passwordConfirmationLink.create'));

        $response->assertRedirect(route('auth.confirmPassword.new'));
        $response->assertSessionHas('status', 'We sent a link to your email address. Open it in this browser to confirm it is you.');

        Notification::assertSentTo($dwight, PasswordConfirmationRequested::class);
    }

    #[Test]
    public function it_stops_sending_links_after_six_in_a_minute(): void
    {
        Notification::fake();

        $dwight = User::factory()->create();

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($dwight)->post(route('auth.passwordConfirmationLink.create'));
        }

        $this->actingAs($dwight)->post(route('auth.passwordConfirmationLink.create'))->assertTooManyRequests();
    }

    #[Test]
    public function it_confirms_the_session_of_the_person_it_was_sent_to(): void
    {
        $dwight = User::factory()->create(['password' => null]);

        $response = $this->actingAs($dwight)
            ->withSession(['url.intended' => route('settings.twoFactor.new')])
            ->get($this->linkFor($dwight));

        $response->assertRedirect(route('settings.twoFactor.new'));
        $this->assertNotNull(session('auth.password_confirmed_at'));
    }

    #[Test]
    public function it_confirms_nothing_for_somebody_else(): void
    {
        $dwight = User::factory()->create();
        $jim = User::factory()->create();

        $response = $this->actingAs($jim)->get($this->linkFor($dwight));

        $response->assertNotFound();
        $this->assertNull(session('auth.password_confirmed_at'));
    }

    #[Test]
    public function it_confirms_nothing_once_the_link_has_expired(): void
    {
        $dwight = User::factory()->create();
        $link = $this->linkFor($dwight);

        $this->travel(6)->minutes();

        $response = $this->actingAs($dwight)->get($link);

        $response->assertForbidden();
        $this->assertNull(session('auth.password_confirmed_at'));
    }

    #[Test]
    public function it_confirms_nothing_when_the_link_was_altered(): void
    {
        $dwight = User::factory()->create();
        $jim = User::factory()->create();

        $forged = str_replace("/link/{$jim->id}?", "/link/{$dwight->id}?", $this->linkFor($jim));

        $response = $this->actingAs($dwight)->get($forged);

        $response->assertForbidden();
        $this->assertNull(session('auth.password_confirmed_at'));
    }
}
