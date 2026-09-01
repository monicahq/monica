<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Docs;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiDocsMarkdownControllerTest extends TestCase
{
    #[Test]
    public function it_hands_the_whole_reference_to_a_visitor_who_is_not_signed_in(): void
    {
        $response = $this->get(route('docs.api.markdown.index'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
    }

    #[Test]
    public function the_whole_reference_holds_every_documented_endpoint(): void
    {
        $response = $this->get(route('docs.api.markdown.index'));

        $response->assertSee('# Check status', escape: false);
        $response->assertSee('# Register', escape: false);
        $response->assertSee('# Log in', escape: false);
        $response->assertSee('# Log out', escape: false);
        $response->assertSee('# Get your profile', escape: false);
        $response->assertSee('# Update your profile', escape: false);
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
    public function it_writes_the_verb_and_the_path_the_way_a_client_would_send_them(): void
    {
        $response = $this->get(route('docs.api.markdown.show', ['section' => 'me-update']));

        $response->assertSee('`PUT /api/v1/me`', escape: false);
    }

    #[Test]
    public function it_carries_the_samples_in_fenced_blocks(): void
    {
        $response = $this->get(route('docs.api.markdown.show', ['section' => 'me-show']));

        $response->assertSee('```sh', escape: false);
        $response->assertSee('```javascript', escape: false);
        $response->assertSee('```php', escape: false);
        $response->assertSee('```json', escape: false);
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
    public function it_says_when_an_endpoint_answers_with_no_body(): void
    {
        $response = $this->get(route('docs.api.markdown.show', ['section' => 'logout-destroy']));

        $response->assertSee('`204`, with no body.', escape: false);
    }

    #[Test]
    public function it_carries_the_fields_of_the_answer_as_bullets(): void
    {
        $response = $this->get(route('docs.api.markdown.show', ['section' => 'vaults-index']));

        $response->assertSee('## Returns');
        $response->assertSee('- `data[].attributes.name` (string):', escape: false);
        $response->assertSee('- `meta.total` (integer):', escape: false);
    }

    #[Test]
    public function it_finds_nothing_for_a_section_that_does_not_exist(): void
    {
        $this->get(route('docs.api.markdown.show', ['section' => 'nothing-of-the-sort']))
            ->assertNotFound();
    }

    #[Test]
    public function the_page_offers_both_ways_of_taking_it_away(): void
    {
        $response = $this->get(route('docs.api.show', ['section' => 'me-show']));

        $response->assertSee('Copy for LLM');
        $response->assertSee('View as Markdown');
        $response->assertSee(route('docs.api.markdown.show', ['section' => 'me-show']), escape: false);
    }
}
