<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\PasskeyCounterAnomaly;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasskeyCounterAnomalyTest extends TestCase
{
    #[Test]
    public function it_says_what_it_is_about_in_the_subject(): void
    {
        $mailable = new PasskeyCounterAnomaly(name: 'Michael MacBook');

        $this->assertEquals(
            'Something is wrong with a passkey on your '.config('app.name').' account',
            $mailable->envelope()->subject,
        );
    }

    #[Test]
    public function it_names_the_passkey_in_the_body(): void
    {
        $rendered = new PasskeyCounterAnomaly(name: 'Michael MacBook')->render();

        $this->assertStringContainsString('Michael MacBook', $rendered);
    }

    #[Test]
    public function it_says_what_to_do_about_it(): void
    {
        $rendered = new PasskeyCounterAnomaly(name: 'Michael MacBook')->render();

        $this->assertStringContainsString('Remove that passkey', $rendered);
    }
}
