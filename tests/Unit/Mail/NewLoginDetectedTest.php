<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\NewLoginDetected;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NewLoginDetectedTest extends TestCase
{
    #[Test]
    public function it_has_a_subject(): void
    {
        config(['app.name' => 'OfficeLife']);

        $this->assertEquals(
            'You signed in to OfficeLife without a password',
            (new NewLoginDetected(ip: '10.0.0.1'))->envelope()->subject,
        );
    }

    #[Test]
    public function it_names_where_the_sign_in_came_from(): void
    {
        $this->assertStringContainsString('10.0.0.1', (new NewLoginDetected(ip: '10.0.0.1'))->render());
    }
}
