<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\MagicLink;
use App\Models\User;
use App\Notifications\MagicLinkCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MagicLinkCreatedTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'aaaabbbbccccddddeeeeffffgggghhhhiiiijjjjkkkkllllmmmmnnnnoooopppp';

    #[Test]
    public function it_has_a_subject(): void
    {
        config(['app.name' => 'Monica']);

        $michael = User::factory()->create();

        $message = $this->notificationFor($this->link())->toMail($michael);

        $this->assertEquals('Your sign-in link for Monica', $message->subject);
    }

    #[Test]
    public function it_carries_the_link_and_how_long_it_lasts(): void
    {
        $michael = User::factory()->create();
        $link = $this->link();

        $message = $this->notificationFor($link)->toMail($michael);

        $this->assertStringContainsString(
            '/magic-link/'.self::TOKEN,
            (string) $message->actionUrl,
        );
        $this->assertStringContainsString('5 minutes', (string) $message->render());
    }

    #[Test]
    public function it_signs_the_link_so_it_cannot_be_forged(): void
    {
        $michael = User::factory()->create();

        $url = (string) $this->notificationFor($this->link())->toMail($michael)->actionUrl;

        $this->assertStringContainsString('signature=', $url);
        $this->assertStringContainsString('expires=', $url);
    }

    #[Test]
    public function it_lets_the_signature_run_out_when_the_link_does(): void
    {
        $michael = User::factory()->create();
        $link = $this->link();

        $url = (string) $this->notificationFor($link)->toMail($michael)->actionUrl;

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertEquals($link->expires_at->timestamp, (int) $query['expires']);
    }

    #[Test]
    public function it_carries_the_token_rather_than_what_the_database_holds(): void
    {
        $michael = User::factory()->create();
        $link = $this->link();

        $rendered = (string) $this->notificationFor($link)->toMail($michael)->render();

        $this->assertStringContainsString(self::TOKEN, $rendered);
        $this->assertStringNotContainsString($link->token, $rendered);
    }

    #[Test]
    public function it_says_what_to_do_if_they_did_not_ask_for_it(): void
    {
        $michael = User::factory()->create();

        $message = $this->notificationFor($this->link())->toMail($michael);

        $this->assertStringContainsString('If you did not ask for this link', (string) $message->panel);
    }

    #[Test]
    public function it_goes_out_as_an_email(): void
    {
        $michael = User::factory()->create();

        $this->assertEquals(['mail'], $this->notificationFor($this->link())->via($michael));
    }

    #[Test]
    public function it_goes_on_the_queue_the_impatient_ones_go_on(): void
    {
        $this->assertEquals('high', $this->notificationFor($this->link())->queue);
    }

    #[Test]
    public function it_belongs_in_the_journal_as_a_sign_in_link(): void
    {
        $this->assertEquals(
            EmailType::MagicLinkCreated,
            $this->notificationFor($this->link())->emailType(),
        );
    }

    private function link(): MagicLink
    {
        return MagicLink::factory()->create([
            'token' => hash('sha256', self::TOKEN),
            'expires_at' => now()->addMinutes(5),
        ]);
    }

    private function notificationFor(MagicLink $link): MagicLinkCreated
    {
        return new MagicLinkCreated(magicLink: $link, token: self::TOKEN, minutes: 5);
    }
}
