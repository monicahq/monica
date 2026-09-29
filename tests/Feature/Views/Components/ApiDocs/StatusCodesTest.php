<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components\ApiDocs;

use App\ViewModels\Docs\ApiDocsSection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatusCodesTest extends TestCase
{
    /** @param  array<string, mixed>  $overrides */
    private function section(array $overrides = []): ApiDocsSection
    {
        return new ApiDocsSection(array_merge([
            'id' => 'errors',
            'title' => 'Errors',
            'method' => 'PUT',
            'path' => '/me',
            'description' => 'What Scranton answers when something goes wrong.',
            'rateLimit' => 'Sixty requests per minute, per token.',
            'statusCodes' => [
                ['status' => 201, 'name' => 'Created', 'when' => 'Jim made a new Dundie.', 'body' => 'The Dundie.'],
                ['status' => 409, 'name' => 'Conflict', 'when' => 'Dwight moved the stapler again.', 'body' => 'A message.'],
            ],
        ], $overrides));
    }

    #[Test]
    public function it_shows_every_code_with_its_name_when_it_happens_and_what_the_body_holds(): void
    {
        $this->blade('<x-api-docs.status-codes :section="$section" />', ['section' => $this->section()])
            ->assertSee('Status codes')
            ->assertSeeInOrder(['201', 'Created', 'Jim made a new Dundie.', 'The Dundie.', '409', 'Conflict', 'Dwight moved the stapler again.', 'A message.']);
    }

    #[Test]
    public function it_names_the_code_as_the_header_of_its_row(): void
    {
        $this->blade('<x-api-docs.status-codes :section="$section" />', ['section' => $this->section()])
            ->assertSee('scope="col"', escape: false)
            ->assertSee('scope="row"', escape: false);
    }

    #[Test]
    public function it_shows_nothing_when_the_section_lists_no_code(): void
    {
        $this->blade('<x-api-docs.status-codes :section="$section" />', ['section' => $this->section(['statusCodes' => []])])
            ->assertDontSee('Status codes');
    }
}
