<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\PasskeyRemoved;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasskeyRemovedTest extends TestCase
{
    #[Test]
    public function it_says_what_it_is_about_in_the_subject(): void
    {
        $mailable = new PasskeyRemoved(name: 'Yubikey in a drawer');

        $this->assertEquals(
            'A passkey was removed from your '.config('app.name').' account',
            $mailable->envelope()->subject,
        );
    }

    #[Test]
    public function it_names_the_passkey_in_the_body(): void
    {
        $rendered = new PasskeyRemoved(name: 'Yubikey in a drawer')->render();

        $this->assertStringContainsString('Yubikey in a drawer', $rendered);
    }

    #[Test]
    public function it_says_what_to_do_if_they_did_not_remove_it(): void
    {
        $rendered = new PasskeyRemoved(name: 'Yubikey in a drawer')->render();

        $this->assertStringContainsString('If you did not remove it', $rendered);
    }
}
