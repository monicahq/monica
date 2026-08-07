<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\LoginFailed;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginFailedTest extends TestCase
{
    #[Test]
    public function it_has_a_subject(): void
    {
        config(['app.name' => 'OfficeLife']);

        $this->assertEquals(
            'Failed sign-in attempt on your OfficeLife account',
            (new LoginFailed)->envelope()->subject,
        );
    }

    #[Test]
    public function it_tells_the_reader_what_to_do_about_it(): void
    {
        $this->assertStringContainsString('change your password', (new LoginFailed)->render());
    }
}
