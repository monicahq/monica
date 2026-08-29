<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\PasswordRemoved;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordRemovedTest extends TestCase
{
    #[Test]
    public function it_says_what_it_is_about_in_the_subject(): void
    {
        $this->assertEquals(
            'Your '.config('app.name').' password was removed',
            new PasswordRemoved()->envelope()->subject,
        );
    }

    #[Test]
    public function it_names_what_signs_them_in_now(): void
    {
        $rendered = new PasswordRemoved()->render();

        $this->assertStringContainsString('Your passkeys, and a link sent to this address', $rendered);
    }

    #[Test]
    public function it_says_what_to_do_if_they_did_not_remove_it(): void
    {
        $this->assertStringContainsString('If you did not remove it', new PasswordRemoved()->render());
    }
}
