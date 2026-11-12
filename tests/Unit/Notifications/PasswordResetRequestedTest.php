<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\PasswordResetRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordResetRequestedTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'aaaabbbbccccddddeeeeffffgggghhhhiiiijjjjkkkkllllmmmmnnnnoooopppp';

    #[Test]
    public function it_carries_the_link_to_the_screen_that_resets_the_password(): void
    {
        $michael = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $message = new PasswordResetRequested(token: self::TOKEN)->toMail($michael);

        $this->assertStringStartsWith(
            route('auth.password.edit', ['token' => self::TOKEN, 'email' => 'michael.scott@dundermifflin.com']),
            $message->actionUrl,
        );
        $this->assertTrue(URL::hasValidSignature(Request::create($message->actionUrl)));
    }

    #[Test]
    public function it_carries_a_link_that_stops_working_when_the_token_expires(): void
    {
        config(['auth.passwords.users.expire' => 60]);

        $michael = User::factory()->create();

        $message = new PasswordResetRequested(token: self::TOKEN)->toMail($michael);

        $this->travel(61)->minutes();

        $this->assertFalse(URL::hasValidSignature(Request::create($message->actionUrl)));
    }

    #[Test]
    public function it_says_how_long_the_link_lasts(): void
    {
        config(['auth.passwords.users.expire' => 60]);

        $michael = User::factory()->create();

        $message = new PasswordResetRequested(token: self::TOKEN)->toMail($michael);

        $this->assertStringContainsString('60 minutes', (string) $message->render());
    }

    #[Test]
    public function it_goes_on_the_queue_the_impatient_ones_go_on(): void
    {
        $this->assertEquals('high', new PasswordResetRequested(token: self::TOKEN)->queue);
    }

    #[Test]
    public function it_belongs_in_the_journal_as_a_password_reset(): void
    {
        $this->assertEquals(
            EmailType::PasswordResetRequested,
            new PasswordResetRequested(token: self::TOKEN)->emailType(),
        );
    }
}
