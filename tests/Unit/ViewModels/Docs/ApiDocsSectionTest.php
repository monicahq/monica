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
}
