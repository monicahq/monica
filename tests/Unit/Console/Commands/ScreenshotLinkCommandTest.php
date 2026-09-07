<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ScreenshotLinkCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_mints_a_link_that_signs_the_user_in(): void
    {
        config()->set('monica.screenshot_link.enabled', true);
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $this->artisan('monica:screenshot-link', [
            'email' => $user->email,
            '--to' => '/settings',
        ])->assertSuccessful();
    }

    #[Test]
    public function the_link_it_mints_works(): void
    {
        config()->set('monica.screenshot_link.enabled', true);
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        Artisan::call('monica:screenshot-link', ['email' => $user->email, '--to' => '/settings']);

        $link = $this->linkFrom(Artisan::output());

        $this->get($link)->assertRedirect('/settings');

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function it_refuses_to_mint_one_while_the_setting_is_off(): void
    {
        config()->set('monica.screenshot_link.enabled', false);
        $user = User::factory()->create();

        $this->artisan('monica:screenshot-link', ['email' => $user->email])->assertFailed();
    }

    #[Test]
    public function it_refuses_to_mint_one_for_an_instance_nobody_develops_on(): void
    {
        config()->set('monica.screenshot_link.enabled', true);
        config()->set('app.url', 'https://monica.example.com');
        $user = User::factory()->create();

        $this->artisan('monica:screenshot-link', ['email' => $user->email])->assertFailed();
    }

    #[Test]
    public function it_refuses_somebody_this_instance_does_not_know(): void
    {
        config()->set('monica.screenshot_link.enabled', true);

        $this->artisan('monica:screenshot-link', ['email' => 'dwight.schrute@dundermifflin.com'])
            ->assertFailed();
    }

    private function linkFrom(string $output): string
    {
        preg_match('#https?://\S+#', $output, $matches);

        $this->assertNotEmpty($matches, 'The command printed no link.');

        return $matches[0];
    }
}
