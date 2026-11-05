<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Docs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RobotsTextControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_serves_the_rules_for_crawlers_to_anybody(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertStringStartsWith("User-agent: *\n", (string) $response->getContent());
    }
}
