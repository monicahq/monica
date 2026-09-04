<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\NewLoginDetected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NewLoginDetectedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_a_subject(): void
    {
        config(['app.name' => 'Monica']);

        $michael = User::factory()->create();

        $message = new NewLoginDetected(ip: '10.0.0.1')->toMail($michael);

        $this->assertEquals('You signed in to Monica without a password', $message->subject);
    }

    #[Test]
    public function it_names_where_the_sign_in_came_from(): void
    {
        $michael = User::factory()->create();

        $message = new NewLoginDetected(ip: '10.0.0.1')->toMail($michael);

        $this->assertStringContainsString('10.0.0.1', (string) $message->render());
    }

    #[Test]
    public function it_says_what_to_do_if_it_was_not_them(): void
    {
        $michael = User::factory()->create();

        $message = new NewLoginDetected(ip: '10.0.0.1')->toMail($michael);

        $this->assertStringContainsString('If this was not you', (string) $message->panel);
    }

    #[Test]
    public function it_goes_out_as_an_email(): void
    {
        $michael = User::factory()->create();

        $this->assertEquals(['mail'], new NewLoginDetected(ip: '10.0.0.1')->via($michael));
    }

    #[Test]
    public function it_belongs_in_the_journal_as_a_new_sign_in(): void
    {
        $this->assertEquals(EmailType::NewLogin, new NewLoginDetected(ip: '10.0.0.1')->emailType());
    }
}
