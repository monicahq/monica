<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationScreenTest extends TestCase
{
    use RefreshDatabase;

    private function secure(): self
    {
        URL::forceScheme('https');

        return $this;
    }

    #[Test]
    public function it_can_post_to_either_route(): void
    {
        $html = str_replace('\\', '', $this->secure()->get(route('auth.register.new'))->getContent());

        $this->assertStringContainsString(route('auth.registerPasskey.create'), $html);
        $this->assertStringContainsString(route('auth.register.create'), $html);
    }
}
