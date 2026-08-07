<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\UserIpChanged;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserIpChangedTest extends TestCase
{
    #[Test]
    public function it_has_a_subject(): void
    {
        config(['app.name' => 'OfficeLife']);

        $mailable = new UserIpChanged(email: 'michael.scott@dundermifflin.com', ip: '10.0.0.1');

        $this->assertEquals(
            'A sign-in from a new place on your OfficeLife account',
            $mailable->envelope()->subject,
        );
    }

    #[Test]
    public function it_names_the_account_and_where_the_sign_in_came_from(): void
    {
        $mailable = new UserIpChanged(email: 'michael.scott@dundermifflin.com', ip: '10.0.0.1');

        $rendered = $mailable->render();

        $this->assertStringContainsString('michael.scott@dundermifflin.com', $rendered);
        $this->assertStringContainsString('10.0.0.1', $rendered);
    }
}
