<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Layouts;

use App\Enums\SignInMethodEnum;
use App\Models\User;
use App\Notifications\NewLoginDetected;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Markdown;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MailLayoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_heading_once(): void
    {
        $rendered = (string) new VerifyEmail()->toMail(User::factory()->create())->render();

        $this->assertSame(1, substr_count($rendered, 'Confirm your email address'));
        $this->assertStringNotContainsString('Hello!', $rendered);
    }

    #[Test]
    public function it_shows_the_full_address_when_the_email_has_an_action(): void
    {
        $message = new VerifyEmail()->toMail(User::factory()->create());

        $rendered = (string) $message->render();

        $this->assertStringContainsString('copy and paste the address below into your browser', $rendered);
        $this->assertStringContainsString('>'.e($message->actionUrl).'</a>', $rendered);
    }

    #[Test]
    public function it_leaves_out_the_address_when_the_email_has_no_action(): void
    {
        $rendered = (string) new NewLoginDetected(ip: '10.0.0.1', method: SignInMethodEnum::EmailLink)->toMail(User::factory()->create())->render();

        $this->assertStringNotContainsString('copy and paste the address below into your browser', $rendered);
    }

    #[Test]
    public function it_shows_the_panel_of_a_sign_in_alert(): void
    {
        $rendered = (string) new NewLoginDetected(ip: '10.0.0.1', method: SignInMethodEnum::EmailLink)->toMail(User::factory()->create())->render();

        $this->assertStringContainsString('If this was not you, change your password now.', $rendered);
    }

    #[Test]
    public function it_states_the_language_of_the_reader(): void
    {
        app()->setLocale('fr_FR');

        $rendered = (string) new VerifyEmail()->toMail(User::factory()->create())->render();

        $this->assertMatchesRegularExpression('/<html[^>]+lang="fr-FR"/', $rendered);
    }

    #[Test]
    public function it_names_the_product_on_the_header_mark_and_leaves_the_footer_mark_unnamed(): void
    {
        config(['app.name' => 'Monica']);

        $rendered = (string) new VerifyEmail()->toMail(User::factory()->create())->render();

        $this->assertMatchesRegularExpression('/<img[^>]+alt="Monica"/', $rendered);
        $this->assertMatchesRegularExpression('/<img[^>]+alt=""/', $rendered);
    }

    #[Test]
    public function it_declares_it_supports_light_and_dark(): void
    {
        $rendered = (string) new VerifyEmail()->toMail(User::factory()->create())->render();

        $this->assertStringContainsString('<meta name="color-scheme" content="light dark">', $rendered);
        $this->assertStringContainsString('<meta name="supported-color-schemes" content="light dark">', $rendered);
    }

    #[Test]
    public function it_loads_every_image_from_the_application(): void
    {
        $rendered = (string) new VerifyEmail()->toMail(User::factory()->create())->render();

        preg_match_all('/<img[^>]+src="([^"]+)"/', $rendered, $sources);

        $this->assertNotEmpty($sources[1]);

        foreach ($sources[1] as $source) {
            $this->assertStringStartsWith(url('/').'/', $source);
        }
    }

    #[Test]
    public function it_keeps_the_layout_markup_out_of_the_plain_text_email(): void
    {
        $message = new VerifyEmail()->toMail(User::factory()->create());

        $text = (string) app(Markdown::class)->renderText($message->markdown, $message->data());

        $this->assertStringContainsString("If you did not create an account, you can safely ignore this email.\n\nThanks,", $text);
        $this->assertStringNotContainsString('<div', $text);
    }
}
