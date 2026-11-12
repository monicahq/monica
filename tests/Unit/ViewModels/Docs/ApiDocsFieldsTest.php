<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Docs;

use App\ViewModels\Docs\ApiDocsFields;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiDocsFieldsTest extends TestCase
{
    /** @var list<array{name: string, type: string, description: string}> */
    private array $attributes = [
        ['name' => 'name', 'type' => 'string', 'description' => 'Up to 255 characters.'],
        ['name' => 'created_at', 'type' => 'timestamp', 'description' => 'A count of seconds.'],
    ];

    #[Test]
    public function it_describes_one_record(): void
    {
        $fields = ApiDocsFields::one('vault', 'vault', $this->attributes);

        $this->assertSame([
            'data',
            'data.type',
            'data.id',
            'data.attributes',
            'data.attributes.name',
            'data.attributes.created_at',
            'data.links',
            'data.links.self',
        ], array_column($fields, 'name'));
    }

    #[Test]
    public function it_describes_the_page_a_record_opens_in_the_application_when_it_has_one(): void
    {
        $fields = ApiDocsFields::one('vault', 'vault', $this->attributes, webLink: 'The page of the vault.');

        $web = collect($fields)->firstWhere('name', 'data.links.web');

        $this->assertSame('The page of the vault.', $web['description']);
        $this->assertSame('data.links.web', array_column($fields, 'name')[array_key_last($fields)]);
    }

    #[Test]
    public function it_leaves_the_links_off_a_record_that_is_only_read_inside_another(): void
    {
        $fields = ApiDocsFields::nested('field', 'field', $this->attributes);

        $this->assertSame([
            'data',
            'data.type',
            'data.id',
            'data.attributes',
            'data.attributes.name',
            'data.attributes.created_at',
        ], array_column($fields, 'name'));
    }

    #[Test]
    public function it_describes_a_page_of_records(): void
    {
        $fields = ApiDocsFields::page('vault', 'vault', 'vaults', $this->attributes);

        $this->assertSame([
            'data',
            'data[]',
            'data[].type',
            'data[].id',
            'data[].attributes',
            'data[].attributes.name',
            'data[].attributes.created_at',
            'data[].links',
            'data[].links.self',
            'meta',
            'meta.page',
            'meta.per_page',
            'meta.total',
        ], array_column($fields, 'name'));
    }

    #[Test]
    public function it_says_which_kind_of_record_the_type_always_holds(): void
    {
        $fields = ApiDocsFields::one('email_sent', 'email', $this->attributes);

        $type = collect($fields)->firstWhere('name', 'data.type');

        $this->assertStringContainsString('email_sent', $type['description']);
    }

    #[Test]
    public function it_carries_a_marker_through_from_an_attribute(): void
    {
        $fields = ApiDocsFields::one('log', 'log entry', [
            ['name' => 'parameters', 'type' => 'object', 'description' => 'Whatever the action recorded.', 'opaque' => true],
        ]);

        $parameters = collect($fields)->firstWhere('name', 'data.attributes.parameters');

        $this->assertTrue($parameters['opaque']);
    }

    #[Test]
    public function it_puts_the_paging_counters_only_on_a_page(): void
    {
        $names = array_column(ApiDocsFields::one('vault', 'vault', $this->attributes), 'name');

        $this->assertNotContains('meta', $names);
        $this->assertNotContains('meta.total', $names);
        $this->assertNotContains('data[]', $names);
    }
}
