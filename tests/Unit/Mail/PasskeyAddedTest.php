<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\PasskeyAdded;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasskeyAddedTest extends TestCase
{
    #[Test]
    public function it_says_what_it_is_about_in_the_subject(): void
    {
        $mailable = new PasskeyAdded(name: 'MacBook Pro');

        $this->assertEquals(
            'A passkey was added to your '.config('app.name').' account',
            $mailable->envelope()->subject,
        );
    }

    #[Test]
    public function it_names_the_passkey_in_the_body(): void
    {
        $rendered = new PasskeyAdded(name: 'MacBook Pro')->render();

        $this->assertStringContainsString('MacBook Pro', $rendered);
    }

    #[Test]
    public function it_says_what_to_do_if_they_did_not_add_it(): void
    {
        $rendered = new PasskeyAdded(name: 'MacBook Pro')->render();

        $this->assertStringContainsString('If you did not add it', $rendered);
    }
}
