<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components\ApiDocs;

use App\ViewModels\Docs\ApiDocsSection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReturnsTest extends TestCase
{
    /** @param  array<string, mixed>  $overrides */
    private function section(array $overrides = []): ApiDocsSection
    {
        return new ApiDocsSection(array_merge([
            'id' => 'dundies-index',
            'title' => 'List Dundies',
            'method' => 'GET',
            'path' => '/dundies',
            'description' => 'Every award nobody asked for.',
            'rateLimit' => 'Sixty requests per minute, per token.',
            'returns' => 'A page of award objects.',
            'returnFields' => [
                ['name' => 'data', 'type' => 'array', 'description' => 'The awards on this page.'],
                ['name' => 'data[]', 'type' => 'object', 'description' => 'One award.'],
                ['name' => 'data[].attributes', 'type' => 'object', 'description' => 'Everything the award holds.'],
                ['name' => 'data[].attributes.category', 'type' => 'string', 'description' => 'Up to 255 characters.'],
                ['name' => 'meta.total', 'type' => 'integer', 'description' => 'How many awards there are in all.'],
            ],
        ], $overrides));
    }

    #[Test]
    public function it_shows_the_summary_above_the_fields(): void
    {
        $this->blade('<x-api-docs.returns :section="$section" />', ['section' => $this->section()])
            ->assertSee('Returns')
            ->assertSee('A page of award objects.')
            ->assertSeeInOrder(['A page of award objects.', 'data'], escape: false);
    }

    #[Test]
    public function it_shows_the_path_the_type_and_the_description_of_every_field(): void
    {
        $response = $this->blade('<x-api-docs.returns :section="$section" />', ['section' => $this->section()]);

        $response->assertSee('data[].attributes.category', escape: false);
        $response->assertSee('string');
        $response->assertSee('Up to 255 characters.');
        $response->assertSee('meta.total', escape: false);
        $response->assertSee('integer');
    }

    #[Test]
    public function it_names_the_parent_in_the_field_itself_rather_than_only_in_the_indentation(): void
    {
        $this->blade('<x-api-docs.returns :section="$section" />', ['section' => $this->section()])
            ->assertSee('data[].attributes.category', escape: false)
            ->assertDontSee('>category<', escape: false);
    }

    #[Test]
    public function it_shows_the_summary_and_no_rows_for_an_endpoint_that_answers_with_nothing(): void
    {
        $response = $this->blade('<x-api-docs.returns :section="$section" />', [
            'section' => $this->section([
                'returns' => 'Nothing at all.',
                'returnFields' => [],
            ]),
        ]);

        $response->assertSee('Returns');
        $response->assertSee('Nothing at all.');
    }

    #[Test]
    public function it_shows_nothing_at_all_when_there_is_neither_a_summary_nor_a_field(): void
    {
        $response = $this->blade('<x-api-docs.returns :section="$section" />', [
            'section' => $this->section(['returns' => null, 'returnFields' => []]),
        ]);

        $response->assertDontSee('Returns');
    }
}
