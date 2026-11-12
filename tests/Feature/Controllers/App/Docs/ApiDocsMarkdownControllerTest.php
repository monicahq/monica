<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Docs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiDocsMarkdownControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hands_the_whole_reference_to_a_visitor_who_is_not_signed_in(): void
    {
        $response = $this->get(route('docs.api.markdown.index'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
    }

    #[Test]
    public function it_hands_over_one_endpoint_on_its_own(): void
    {
        $response = $this->get(route('docs.api.markdown.show', ['section' => 'me-show']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
        $response->assertSee('# Get your profile', escape: false);
        $response->assertDontSee('# Log out', escape: false);
    }

    #[Test]
    public function it_escapes_nothing_because_it_is_not_a_web_page(): void
    {
        $response = $this->get(route('docs.api.markdown.show', ['section' => 'me-show']));

        $response->assertDontSee('&quot;', escape: false);
        $response->assertDontSee('&#039;', escape: false);
        $response->assertDontSee('&gt;', escape: false);
    }

    #[Test]
    public function it_finds_nothing_for_a_section_that_does_not_exist(): void
    {
        $this->get(route('docs.api.markdown.show', ['section' => 'nothing-of-the-sort']))
            ->assertNotFound();
    }
}
