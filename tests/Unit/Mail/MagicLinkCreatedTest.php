<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\MagicLinkCreated;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MagicLinkCreatedTest extends TestCase
{
    #[Test]
    public function it_has_a_subject(): void
    {
        config(['app.name' => 'OfficeLife']);

        $mailable = new MagicLinkCreated(url: 'https://officelife.test/magic-link/abc', minutes: 5);

        $this->assertEquals('Your sign-in link for OfficeLife', $mailable->envelope()->subject);
    }

    #[Test]
    public function it_carries_the_link_and_how_long_it_lasts(): void
    {
        $mailable = new MagicLinkCreated(url: 'https://officelife.test/magic-link/abc', minutes: 5);

        $rendered = $mailable->render();

        $this->assertStringContainsString('https://officelife.test/magic-link/abc', $rendered);
        $this->assertStringContainsString('5 minutes', $rendered);
    }
}
