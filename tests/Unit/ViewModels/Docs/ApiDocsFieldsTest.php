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

        $this->assertStringContainsString('Always email_sent', $type['description']);
    }

    #[Test]
    public function it_keeps_the_type_and_the_description_of_an_attribute(): void
    {
        $fields = ApiDocsFields::page('vault', 'vault', 'vaults', $this->attributes);

        $created = collect($fields)->firstWhere('name', 'data[].attributes.created_at');

        $this->assertSame('timestamp', $created['type']);
        $this->assertSame('A count of seconds.', $created['description']);
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
    public function it_names_the_records_in_the_plural_on_a_page_and_in_the_singular_on_one(): void
    {
        $page = collect(ApiDocsFields::page('vault', 'vault', 'vaults', $this->attributes));

        $this->assertStringContainsString('vaults on this page', $page->firstWhere('name', 'data')['description']);
        $this->assertStringContainsString('One vault.', $page->firstWhere('name', 'data[]')['description']);
        $this->assertStringContainsString('vaults there are in all', $page->firstWhere('name', 'meta.total')['description']);

        $one = collect(ApiDocsFields::one('vault', 'vault', $this->attributes));

        $this->assertSame('The vault.', $one->firstWhere('name', 'data')['description']);
    }

    #[Test]
    public function it_puts_the_paging_counters_only_on_a_page(): void
    {
        $names = array_column(ApiDocsFields::one('vault', 'vault', $this->attributes), 'name');

        $this->assertNotContains('meta', $names);
        $this->assertNotContains('meta.total', $names);
        $this->assertNotContains('data[]', $names);
    }

    #[Test]
    public function it_gives_every_row_a_name_a_type_and_a_description(): void
    {
        foreach (ApiDocsFields::page('vault', 'vault', 'vaults', $this->attributes) as $field) {
            $this->assertArrayHasKey('name', $field);
            $this->assertArrayHasKey('type', $field);
            $this->assertArrayHasKey('description', $field);
            $this->assertNotSame('', $field['description']);
        }
    }
}
