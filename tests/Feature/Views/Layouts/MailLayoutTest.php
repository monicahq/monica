<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Layouts;

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Markdown;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MailLayoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_full_address_when_the_email_has_an_action(): void
    {
        $message = new VerifyEmail()->toMail(User::factory()->create());

        $rendered = (string) $message->render();

        $this->assertStringContainsString('>'.e($message->actionUrl).'</a>', $rendered);
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

        $this->assertStringNotContainsString('<div', $text);
    }
}
