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
    public function it_names_the_one_kind_of_parameter_an_endpoint_takes(): void
    {
        $param = [['name' => 'vault', 'type' => 'uuid', 'required' => true, 'description' => 'Which one.']];

        $path = new ApiDocsSection([...$this->minimum, 'pathParams' => $param]);
        $query = new ApiDocsSection([...$this->minimum, 'queryParams' => $param]);
        $body = new ApiDocsSection([...$this->minimum, 'bodyParams' => $param]);

        $this->assertSame('Path parameters', $path->paramsTitle);
        $this->assertSame('Query parameters', $query->paramsTitle);
        $this->assertSame('Body parameters', $body->paramsTitle);
    }

    #[Test]
    public function it_falls_back_to_the_plain_word_when_an_endpoint_takes_more_than_one_kind(): void
    {
        $param = [['name' => 'vault', 'type' => 'uuid', 'required' => true, 'description' => 'Which one.']];

        $section = new ApiDocsSection([...$this->minimum, 'pathParams' => $param, 'queryParams' => $param]);

        $this->assertSame('Parameters', $section->paramsTitle);
    }

    #[Test]
    public function it_has_no_return_fields_when_the_definition_names_none(): void
    {
        $section = new ApiDocsSection($this->minimum);

        $this->assertSame([], $section->returnFields);
        $this->assertFalse($section->hasReturnFields());
    }
}
