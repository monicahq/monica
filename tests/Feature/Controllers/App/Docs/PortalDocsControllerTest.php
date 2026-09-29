<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Docs;

use App\Http\Middleware\InsertServerGenerationTime;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PortalDocsControllerTest extends TestCase
{
    #[Test]
    public function it_shows_the_start_of_the_guide_to_a_visitor_who_is_not_signed_in(): void
    {
        $response = $this->get('/docs/en');

        $response->assertOk();
        $response->assertSee('Welcome to the Monica guide');
        $response->assertSee('aria-label="Documentation"', false);
    }

    #[Test]
    public function it_shows_the_index_page_of_a_section_at_the_address_of_the_section(): void
    {
        $response = $this->get('/docs/en/start-here');

        $response->assertOk();
        $response->assertSee('<h1', false);
        $response->assertSee('Start here');
    }

    #[Test]
    public function it_shows_a_page_of_a_section(): void
    {
        $response = $this->get('/docs/en/start-here/record-your-first-person');

        $response->assertOk();
        $response->assertSee('Record your first person');
        $response->assertSee('href="#step-1-add-rachel"', false);
    }

    #[Test]
    public function it_prints_how_long_the_server_took_in_place_of_the_marker(): void
    {
        $response = $this->get('/docs/en/start-here/record-your-first-person');

        $response->assertDontSee(InsertServerGenerationTime::MARKER, false);
        $response->assertSeeText('Server generation:');
        $response->assertHeader('Server-Timing');
        $this->assertMatchesRegularExpression('/\d+ ms/', (string) $response->getContent());
    }

    #[Test]
    public function it_answers_not_found_for_a_page_or_a_language_that_does_not_exist(): void
    {
        $this->get('/docs/en/start-here/the-warehouse')->assertNotFound();
        $this->get('/docs/en/the-warehouse')->assertNotFound();
        $this->get('/docs/de')->assertNotFound();
    }
}
