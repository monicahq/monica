<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\VerifyEmail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VerifyEmailTest extends TestCase
{
    #[Test]
    public function it_has_a_subject(): void
    {
        $mailable = new VerifyEmail(url: 'https://officelife.test/verify-email/1/abc');

        $this->assertEquals('Confirm your email address', $mailable->envelope()->subject);
    }

    #[Test]
    public function it_carries_the_link_to_confirm_the_address(): void
    {
        $mailable = new VerifyEmail(url: 'https://officelife.test/verify-email/1/abc');

        $this->assertStringContainsString('https://officelife.test/verify-email/1/abc', $mailable->render());
    }
}
