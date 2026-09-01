<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Docs;

use App\ViewModels\Docs\ApiDocsSection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiDocsSectionTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $minimum = [
        'id' => 'vaults-index',
        'title' => 'List vaults',
        'method' => 'GET',
        'path' => '/vaults',
        'description' => 'Every vault you hold.',
        'rateLimit' => 'Sixty requests per minute, per token.',
    ];

    #[Test]
    public function it_has_no_return_fields_when_the_definition_names_none(): void
    {
        $section = new ApiDocsSection($this->minimum);

        $this->assertSame([], $section->returnFields);
        $this->assertFalse($section->hasReturnFields());
    }

    #[Test]
    public function it_reads_the_return_fields_back_unchanged(): void
    {
        $fields = [
            ['name' => 'data', 'type' => 'array', 'description' => 'The vaults on this page.'],
            ['name' => 'data[].attributes.name', 'type' => 'string', 'description' => 'Up to 255 characters.'],
            ['name' => 'data[].attributes.parameters', 'type' => 'object', 'description' => 'Whatever the action recorded.', 'opaque' => true],
        ];

        $section = new ApiDocsSection([...$this->minimum, 'returnFields' => $fields]);

        $this->assertTrue($section->hasReturnFields());
        $this->assertSame($fields, $section->returnFields);
    }

    #[Test]
    public function it_keeps_the_summary_and_the_field_list_apart(): void
    {
        $section = new ApiDocsSection([
            ...$this->minimum,
            'returns' => 'A page of vault objects, and how many there are in all.',
            'returnFields' => [['name' => 'data', 'type' => 'array', 'description' => 'The vaults on this page.']],
        ]);

        $this->assertSame('A page of vault objects, and how many there are in all.', $section->returns);
        $this->assertCount(1, $section->returnFields);
    }
}
