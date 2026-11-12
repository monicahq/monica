<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ScreenshotLinkControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_signs_the_user_in_and_lands_on_the_screen_that_was_asked_for(): void
    {
        config()->set('monica.screenshot_link.enabled', true);
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $this->get($this->link($user, '/settings'))
            ->assertRedirect('/settings');

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function it_is_refused_while_the_setting_is_off(): void
    {
        config()->set('monica.screenshot_link.enabled', true);
        $user = User::factory()->create();
        $link = $this->link($user, '/settings');

        config()->set('monica.screenshot_link.enabled', false);

        $this->get($link)->assertStatus(404);

        $this->assertGuest();
    }

    #[Test]
    public function it_is_refused_on_an_instance_that_is_not_a_development_one(): void
    {
        config()->set('monica.screenshot_link.enabled', true);
        $user = User::factory()->create();
        $link = $this->link($user, '/settings');

        $this->app['env'] = 'production';

        $this->get($link)->assertStatus(404);

        $this->assertGuest();
    }

    #[Test]
    public function it_is_refused_on_a_host_nobody_develops_on(): void
    {
        config()->set('monica.screenshot_link.enabled', true);
        URL::forceRootUrl('https://monica.example.com');
        $user = User::factory()->create();

        $this->get($this->link($user, '/settings'))->assertStatus(404);

        $this->assertGuest();
    }

    #[Test]
    #[DataProvider('developmentHosts')]
    public function it_is_allowed_on_a_host_somebody_develops_on(string $url): void
    {
        config()->set('monica.screenshot_link.enabled', true);
        URL::forceRootUrl($url);
        $user = User::factory()->create();

        $this->get($this->link($user, '/settings'))->assertRedirect('/settings');

        $this->assertAuthenticatedAs($user);
    }

    /** @return array<string, array<int, string>> */
    public static function developmentHosts(): array
    {
        return [
            'localhost' => ['http://localhost'],
            'a loopback address' => ['http://127.0.0.1'],
            'a parked site' => ['http://erica.test'],
        ];
    }

    #[Test]
    public function it_is_refused_without_a_signature(): void
    {
        config()->set('monica.screenshot_link.enabled', true);
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $this->get('/screenshot-link/michael.scott@dundermifflin.com?to=/settings')
            ->assertStatus(403);

        $this->assertGuest();
    }

    #[Test]
    public function it_is_refused_when_the_screen_it_lands_on_was_tampered_with(): void
    {
        config()->set('monica.screenshot_link.enabled', true);
        $user = User::factory()->create();
        $link = $this->link($user, '/settings');

        $this->get(str_replace('%2Fsettings', '%2Flogs', $link))
            ->assertStatus(403);

        $this->assertGuest();
    }

    #[Test]
    public function it_is_refused_for_somebody_this_instance_does_not_know(): void
    {
        config()->set('monica.screenshot_link.enabled', true);

        $link = URL::temporarySignedRoute(
            'auth.screenshotLink.show',
            now()->addMinutes(5),
            ['email' => 'dwight.schrute@dundermifflin.com', 'to' => '/'],
        );

        $this->get($link)->assertStatus(404);

        $this->assertGuest();
    }

    #[Test]
    #[DataProvider('somewhereElse')]
    public function it_never_lands_anywhere_but_this_application(string $wanted): void
    {
        config()->set('monica.screenshot_link.enabled', true);
        $user = User::factory()->create();

        $this->get($this->link($user, $wanted))->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    /** @return array<string, array<int, string>> */
    public static function somewhereElse(): array
    {
        return [
            'another host' => ['//evil.example'],
            'another host, behind a tab' => ["/\t//evil.example"],
            'another host, behind a newline' => ["/\n/\\evil.example"],
            'another host, backslashed' => ['/\\evil.example'],
            'an absolute address' => ['https://evil.example/settings'],
            'a path of its own' => ['settings'],
            'nothing at all' => [''],
        ];
    }

    private function link(User $user, string $to = '/'): string
    {
        return URL::temporarySignedRoute(
            'auth.screenshotLink.show',
            now()->addMinutes(5),
            ['email' => $user->email, 'to' => $to],
        );
    }
}
