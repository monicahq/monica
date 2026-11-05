<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthPageTest extends TestCase
{
    #[Test]
    public function it_links_a_visitor_to_the_documentation(): void
    {
        $this->blade('<x-auth-page title="Sign in">Welcome back, Dwight.</x-auth-page>')
            ->assertSee('href="'.route('docs.index').'"', escape: false)
            ->assertSee('Docs', escape: false);
    }
}
