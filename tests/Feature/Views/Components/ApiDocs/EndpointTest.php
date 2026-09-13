<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components\ApiDocs;

use App\ViewModels\Docs\ApiDocsSection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EndpointTest extends TestCase
{
    private function section(array $overrides = []): ApiDocsSection
    {
        return new ApiDocsSection(array_merge([
            'id' => 'dundies-create',
            'title' => 'Hand out a Dundie',
            'method' => 'POST',
            'path' => '/dundies',
            'description' => 'Gives somebody an award nobody asked for.',
            'rateLimit' => 'Thirty requests per minute, per token.',
            'returns' => 'The award.',
            'bodyParams' => [
                [
                    'name' => 'recipient',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Who it goes to.',
                    'example' => 'Michael',
                ],
                [
                    'name' => 'category',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Which award it is.',
                ],
            ],
            'response' => ['awarded' => true],
        ], $overrides));
    }

    #[Test]
    public function it_shows_the_verb_and_the_versioned_path(): void
    {
        $this->blade('<x-api-docs.endpoint :section="$section" />', ['section' => $this->section()])
            ->assertSee('POST')
            ->assertSee('/api/v1/dundies');
    }

    #[Test]
    public function it_links_to_its_own_page_as_markdown(): void
    {
        $this->blade('<x-api-docs.endpoint :section="$section" />', ['section' => $this->section()])
            ->assertSee(route('docs.api.markdown.show', ['section' => 'dundies-create']), escape: false);
    }

    #[Test]
    public function it_says_in_words_which_parameters_are_required(): void
    {
        $response = $this->blade('<x-api-docs.endpoint :section="$section" />', ['section' => $this->section()]);

        $response->assertSee('Body parameters');
        $response->assertSee('required');
        $response->assertSee('optional');
        $response->assertSee('Who it goes to.');
    }

    #[Test]
    public function it_says_what_the_endpoint_costs_against_the_rate_limit(): void
    {
        $this->blade('<x-api-docs.endpoint :section="$section" />', ['section' => $this->section()])
            ->assertSee('Thirty requests per minute, per token.')
            ->assertSee('X-RateLimit-Remaining');
    }

    #[Test]
    public function it_offers_the_request_in_three_languages(): void
    {
        $this->blade('<x-api-docs.endpoint :section="$section" />', ['section' => $this->section()])
            ->assertSee('cURL')
            ->assertSee('JavaScript')
            ->assertSee('PHP');
    }

    #[Test]
    public function it_marks_an_endpoint_that_needs_no_token(): void
    {
        $this->blade('<x-api-docs.endpoint :section="$section" />', ['section' => $this->section(['auth' => false])])
            ->assertSee('no token');

        $this->blade('<x-api-docs.endpoint :section="$section" />', ['section' => $this->section()])
            ->assertDontSee('no token');
    }

    #[Test]
    public function it_says_no_content_rather_than_showing_an_empty_box(): void
    {
        $section = $this->section(['responseStatus' => 204, 'response' => null]);

        $this->blade('<x-api-docs.endpoint :section="$section" />', ['section' => $section])
            ->assertSee('No content.')
            ->assertSee('204');
    }
}
