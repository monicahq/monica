<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Docs;

use App\ViewModels\Docs\ApiDocsMarkdown;
use App\ViewModels\Docs\ApiDocsSection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiDocsMarkdownTest extends TestCase
{
    #[Test]
    public function it_opens_an_endpoint_with_its_name_and_the_call_a_client_would_send(): void
    {
        $written = $this->render();

        $this->assertStringStartsWith("# Get a widget\n\n`GET /api/v1/widgets/{widget}`", $written);
    }

    #[Test]
    public function it_says_when_an_endpoint_asks_for_no_token(): void
    {
        $this->assertStringContainsString('No token required.', $this->render(['auth' => false]));
        $this->assertStringNotContainsString('No token required.', $this->render(['auth' => true]));
    }

    #[Test]
    public function it_writes_the_paragraphs_of_the_description_one_after_another(): void
    {
        $written = $this->render(['body' => ['The first thing.', 'The second thing.']]);

        $this->assertStringContainsString("The widget itself.\n\nThe first thing.\n\nThe second thing.", $written);
    }

    #[Test]
    public function it_writes_a_parameter_as_a_bullet_saying_its_type_and_whether_it_is_asked_for(): void
    {
        $written = $this->render();

        $this->assertStringContainsString('## Body parameters', $written);
        $this->assertStringContainsString(
            "- `name` (string, required): What it is called.\n- `colour` (string, optional, defaults to blue): What shade it is.",
            $written,
        );
    }

    #[Test]
    public function it_leaves_the_parameters_out_when_an_endpoint_takes_none(): void
    {
        $written = $this->render(['bodyParams' => []]);

        $this->assertStringNotContainsString('parameters', $written);
    }

    #[Test]
    public function it_writes_the_fields_of_the_answer_as_bullets_under_returns(): void
    {
        $written = $this->render();

        $this->assertStringContainsString('## Returns', $written);
        $this->assertStringContainsString('The widget that was asked for.', $written);
        $this->assertStringContainsString('- `id` (integer): Its identifier.', $written);
    }

    #[Test]
    public function it_leaves_returns_out_when_an_endpoint_promises_nothing_back(): void
    {
        $written = $this->render(['returns' => null, 'returnFields' => []]);

        $this->assertStringNotContainsString('## Returns', $written);
    }

    #[Test]
    public function it_writes_the_status_codes_as_a_table_after_returns(): void
    {
        $written = $this->render(['statusCodes' => [
            ['status' => 409, 'name' => 'Conflict', 'when' => 'Dwight moved the stapler again.', 'body' => 'A message.'],
        ]]);

        $this->assertStringContainsString(
            "## Status codes\n\n| Code | When | Body |\n| --- | --- | --- |\n| `409` Conflict | Dwight moved the stapler again. | A message. |",
            $written,
        );
        $this->assertStringContainsString("- `id` (integer): Its identifier.\n\n## Status codes", $written);
    }

    #[Test]
    public function it_leaves_the_status_codes_out_when_an_endpoint_lists_none(): void
    {
        $this->assertStringNotContainsString('## Status codes', $this->render());
    }

    #[Test]
    public function it_says_the_budget_every_endpoint_answers_within(): void
    {
        $written = $this->render();

        $this->assertStringContainsString(
            "## Rate limit\n\nSixty a minute. Every answer carries the budget left in `X-RateLimit-Remaining`.",
            $written,
        );
    }

    #[Test]
    public function it_fences_a_sample_in_each_language_under_the_name_that_language_is_highlighted_by(): void
    {
        $written = $this->render();

        $this->assertStringContainsString("## Request\n\n```sh", $written);
        $this->assertStringContainsString('```javascript', $written);
        $this->assertStringContainsString('```php', $written);
    }

    #[Test]
    public function it_fences_the_answer_as_json_under_the_status_it_comes_back_with(): void
    {
        $written = $this->render();

        $this->assertStringContainsString("## Response\n\n`200`\n\n```json", $written);
        $this->assertStringContainsString('"name": "Stapler"', $written);
    }

    #[Test]
    public function it_says_so_when_an_endpoint_answers_with_no_body_at_all(): void
    {
        $written = $this->render(['response' => null, 'responseStatus' => 204]);

        $this->assertStringContainsString("## Response\n\n`204`, with no body.", $written);
        $this->assertStringNotContainsString('```json', $written);
    }

    #[Test]
    public function it_rules_a_line_between_one_endpoint_and_the_next(): void
    {
        $written = new ApiDocsMarkdown([$this->section(), $this->section(['title' => 'Delete a widget'])])->render();

        $this->assertStringContainsString("\n\n---\n\n# Delete a widget", $written);
    }

    #[Test]
    public function it_ends_on_a_single_newline_and_opens_on_none(): void
    {
        $written = $this->render();

        $this->assertStringEndsWith("`\n", $written);
        $this->assertSame(ltrim($written), $written);
    }

    #[Test]
    public function it_writes_nothing_but_a_newline_when_there_is_no_endpoint_to_write(): void
    {
        $this->assertSame("\n", new ApiDocsMarkdown([])->render());
    }

    /** @param  array<string, mixed>  $overrides */
    private function render(array $overrides = []): string
    {
        return new ApiDocsMarkdown([$this->section($overrides)])->render();
    }

    /** @param  array<string, mixed>  $overrides */
    private function section(array $overrides = []): ApiDocsSection
    {
        return new ApiDocsSection(array_merge([
            'id' => 'widget-show',
            'title' => 'Get a widget',
            'method' => 'GET',
            'path' => '/widgets/{widget}',
            'examplePath' => '/widgets/1',
            'description' => 'The widget itself.',
            'rateLimit' => 'Sixty a minute.',
            'returns' => 'The widget that was asked for.',
            'returnFields' => [
                ['name' => 'id', 'type' => 'integer', 'description' => 'Its identifier.'],
            ],
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'What it is called.', 'example' => 'Stapler'],
                ['name' => 'colour', 'type' => 'string', 'required' => false, 'description' => 'What shade it is.', 'default' => 'blue'],
            ],
            'response' => ['name' => 'Stapler'],
        ], $overrides));
    }
}
