<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\PasswordSet;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordSetTest extends TestCase
{
    #[Test]
    public function it_says_what_it_is_about_in_the_subject(): void
    {
        $this->assertEquals(
            'A password was set on your '.config('app.name').' account',
            new PasswordSet()->envelope()->subject,
        );
    }

    #[Test]
    public function it_says_a_passkey_still_works_too(): void
    {
        $rendered = new PasswordSet()->render();

        $this->assertStringContainsString('as well as with any passkey you hold', $rendered);
    }

    #[Test]
    public function it_says_what_to_do_if_they_did_not_set_it(): void
    {
        $this->assertStringContainsString('If you did not set it', new PasswordSet()->render());
    }
}
