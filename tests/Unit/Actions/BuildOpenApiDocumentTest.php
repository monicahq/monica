<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\BuildOpenApiDocument;
use App\ViewModels\Docs\ApiDocsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BuildOpenApiDocumentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_asks_for_a_token_everywhere_unless_an_endpoint_says_otherwise(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $this->assertSame([['bearerAuth' => []]], $document['security']);
        $this->assertSame('http', $document['components']['securitySchemes']['bearerAuth']['type']);
        $this->assertSame('bearer', $document['components']['securitySchemes']['bearerAuth']['scheme']);
    }

    #[Test]
    public function it_lets_the_three_endpoints_that_need_no_token_be_reached_without_one(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $this->assertSame([], $document['paths']['/status']['get']['security']);
        $this->assertSame([], $document['paths']['/register']['post']['security']);
        $this->assertSame([], $document['paths']['/login']['post']['security']);
    }

    #[Test]
    public function it_leaves_every_other_endpoint_to_the_credential_the_document_asks_for_globally(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $this->assertArrayNotHasKey('security', $document['paths']['/vaults']['get']);
    }

    #[Test]
    public function it_describes_no_path_a_guide_page_only_borrowed_to_show_a_sample(): void
    {
        $this->assertEqualsCanonicalizing($this->registered(), $this->described());
    }

    #[Test]
    public function it_describes_every_endpoint_the_api_answers(): void
    {
        $described = $this->described();
        $missing = [];

        foreach ($this->registered() as $endpoint) {
            if (! in_array($endpoint, $described, true)) {
                $missing[] = $endpoint;
            }
        }

        $this->assertSame([], $missing, "These endpoints answer but the document describes none of them, so a caller generating a client would not reach them. Document them in resources/docs/api:\n\n".implode("\n", $missing));
    }

    #[Test]
    public function it_describes_no_endpoint_the_api_does_not_answer(): void
    {
        $registered = $this->registered();
        $extra = [];

        foreach ($this->described() as $endpoint) {
            if (! in_array($endpoint, $registered, true)) {
                $extra[] = $endpoint;
            }
        }

        $this->assertSame([], $extra, "The document promises these endpoints and nothing answers them, so a generated client would call into nothing:\n\n".implode("\n", $extra));
    }

    #[Test]
    public function it_leaves_out_an_endpoint_switched_off_for_every_account(): void
    {
        Feature::for(null)->deactivate('vcard-export');
        Feature::flushCache();

        $this->assertNotContains('GET /vaults/{vault}/contacts/{contact}/vcard', $this->described());
    }

    /** @return list<string> */
    private function described(): array
    {
        $described = [];

        foreach (new BuildOpenApiDocument()->execute()['paths'] as $path => $operations) {
            foreach (array_keys($operations) as $method) {
                $described[] = mb_strtoupper((string) $method).' '.$path;
            }
        }

        return $described;
    }

    /** @return list<string> */
    private function registered(): array
    {
        $registered = [];

        foreach (new ApiDocsViewModel()->registeredRoutes() as $route) {
            $registered[] = "{$route['method']} {$route['path']}";
        }

        return $registered;
    }

    #[Test]
    public function it_names_a_group_on_every_endpoint_so_a_generated_client_is_not_one_flat_list(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $tags = array_column($document['tags'], 'name');

        foreach ($document['paths'] as $path => $operations) {
            foreach ($operations as $method => $operation) {
                $this->assertNotEmpty($operation['tags'], "{$method} {$path} belongs to no group");
                $this->assertContains($operation['tags'][0], $tags);
            }
        }
    }

    #[Test]
    public function it_gives_every_endpoint_a_name_a_generator_can_turn_into_a_method(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $ids = [];

        foreach ($document['paths'] as $operations) {
            foreach ($operations as $operation) {
                $ids[] = $operation['operationId'];
            }
        }

        $this->assertSame($ids, array_unique($ids));
        $this->assertNotContains('', $ids);
    }

    #[Test]
    public function it_says_what_every_endpoint_answers_when_it_works(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        foreach ($document['paths'] as $path => $operations) {
            foreach ($operations as $method => $operation) {
                $successes = array_filter(
                    array_keys($operation['responses']),
                    fn (int|string $status): bool => (int) $status < 300,
                );

                $this->assertCount(1, $successes, "{$method} {$path} describes no answer of its own");
            }
        }
    }

    #[Test]
    public function it_reads_back_no_body_from_an_endpoint_that_sends_none(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $this->assertArrayNotHasKey('content', $document['paths']['/logout']['delete']['responses'][204]);
    }

    #[Test]
    public function it_describes_every_refusal_a_caller_can_be_given(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $vault = $document['paths']['/vaults/{vault}']['put']['responses'];

        $this->assertArrayHasKey(401, $vault);
        $this->assertArrayHasKey(404, $vault);
        $this->assertArrayHasKey(422, $vault);
        $this->assertArrayHasKey(429, $vault);
    }

    #[Test]
    public function it_asks_for_no_credential_refusal_where_no_credential_is_asked_for(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $this->assertArrayNotHasKey(401, $document['paths']['/status']['get']['responses']);
        $this->assertArrayHasKey(429, $document['paths']['/status']['get']['responses']);
    }

    #[Test]
    public function it_describes_query_beside_every_read_with_its_parameters_in_the_body(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $query = $document['paths']['/vaults/{vault}/contacts']['query'];

        $this->assertSame('contacts-index-query', $query['operationId']);
        $this->assertArrayHasKey('per_page', $query['requestBody']['content']['application/json']['schema']['properties']);
        $this->assertArrayNotHasKey('query', $document['paths']['/logout']);
    }

    #[Test]
    public function it_marks_a_path_parameter_as_one_a_caller_cannot_leave_out(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $parameter = $document['paths']['/vaults/{vault}']['get']['parameters'][0];

        $this->assertSame('vault', $parameter['name']);
        $this->assertSame('path', $parameter['in']);
        $this->assertTrue($parameter['required']);
    }

    #[Test]
    public function it_describes_how_a_collection_is_paged(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $names = array_column($document['paths']['/vaults']['get']['parameters'], 'name');

        $this->assertContains('page', $names);
        $this->assertContains('per_page', $names);
    }

    #[Test]
    public function it_gives_a_number_back_as_a_number_where_the_reference_wrote_it_as_words(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        foreach ($document['paths']['/vaults']['get']['parameters'] as $parameter) {
            if ($parameter['name'] !== 'per_page') {
                continue;
            }

            $this->assertSame('integer', $parameter['schema']['type']);
            $this->assertSame(25, $parameter['schema']['default']);
        }
    }

    #[Test]
    public function it_asks_for_a_body_where_the_endpoint_takes_one(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $body = $document['paths']['/vaults']['post']['requestBody'];

        $this->assertTrue($body['required']);
        $this->assertArrayHasKey('name', $body['content']['application/json']['schema']['properties']);
        $this->assertContains('name', $body['content']['application/json']['schema']['required']);
    }

    #[Test]
    public function it_asks_for_a_file_the_way_a_file_is_actually_sent(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $body = $document['paths']['/vaults/{vault}/contacts/{contact}/avatar']['post']['requestBody'];

        $this->assertArrayHasKey('multipart/form-data', $body['content']);
        $this->assertSame('binary', $body['content']['multipart/form-data']['schema']['properties']['avatar']['format']);
    }

    #[Test]
    public function it_folds_the_dotted_names_of_the_reference_back_into_the_shape_of_an_answer(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $schema = $document['paths']['/vaults/{vault}']['get']['responses'][200]['content']['application/json']['schema'];

        $this->assertSame(['object', 'null'], $schema['properties']['data']['type']);
        $this->assertSame(['string', 'null'], $schema['properties']['data']['properties']['id']['type']);
        $this->assertArrayHasKey('name', $schema['properties']['data']['properties']['attributes']['properties']);
    }

    #[Test]
    public function it_describes_the_items_of_a_list_rather_than_the_list_alone(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $schema = $document['paths']['/vaults']['get']['responses'][200]['content']['application/json']['schema'];

        $this->assertSame(['array', 'null'], $schema['properties']['data']['type']);
        $this->assertArrayHasKey('attributes', $schema['properties']['data']['items']['properties']);
    }

    #[Test]
    public function it_reads_a_moment_as_the_count_of_seconds_the_api_answers_with(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $attributes = $document['paths']['/vaults/{vault}']['get']['responses'][200]['content']['application/json']['schema']['properties']['data']['properties']['attributes']['properties'];

        $this->assertSame(['integer', 'null'], $attributes['created_at']['type']);
    }

    #[Test]
    public function it_describes_a_refusal_once_and_points_at_it_from_everywhere(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $refusals = $document['paths']['/vaults/{vault}']['put']['responses'];

        $this->assertSame('#/components/schemas/Error', $refusals[401]['content']['application/json']['schema']['$ref']);
        $this->assertSame('#/components/schemas/Error', $refusals[404]['content']['application/json']['schema']['$ref']);
        $this->assertSame('#/components/schemas/ValidationError', $refusals[422]['content']['application/json']['schema']['$ref']);
        $this->assertArrayHasKey('Error', $document['components']['schemas']);
        $this->assertArrayHasKey('ValidationError', $document['components']['schemas']);
    }

    #[Test]
    public function it_describes_the_counts_of_a_page_once_and_points_at_them_from_everywhere(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $schema = $document['paths']['/vaults']['get']['responses'][200]['content']['application/json']['schema'];

        $this->assertSame('#/components/schemas/PaginationMeta', $schema['properties']['meta']['$ref']);
        $this->assertArrayHasKey('PaginationMeta', $document['components']['schemas']);
    }

    #[Test]
    public function it_leaves_a_record_where_it_stands_because_one_type_is_not_one_shape(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $read = $document['paths']['/vaults/{vault}']['get']['responses'][200]['content']['application/json']['schema'];

        $this->assertArrayNotHasKey('$ref', $read['properties']['data']);
    }

    #[Test]
    public function it_shows_a_caller_what_a_request_body_looks_like(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $example = $document['paths']['/vaults']['post']['requestBody']['content']['application/json']['example'];

        $this->assertArrayHasKey('name', $example);
    }

    #[Test]
    public function it_leaves_out_of_the_example_what_the_reference_gave_no_example_for(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $body = $document['paths']['/vaults']['post']['requestBody']['content']['application/json'];

        foreach ($body['example'] as $value) {
            $this->assertNotNull($value);
        }
    }

    #[Test]
    public function it_folds_a_nested_body_parameter_instead_of_naming_it_with_its_dots(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $schema = $document['paths']['/vaults/{vault}/contacts/{contact}/merge/{other}']['post']['requestBody']['content']['application/json']['schema'];

        $this->assertArrayNotHasKey('resolutions[].keep', $schema['properties']);
        $this->assertArrayHasKey('keep', $schema['properties']['resolutions']['items']['properties']);
        $this->assertContains('keep', $schema['properties']['resolutions']['items']['required']);
    }

    #[Test]
    public function it_shows_no_example_body_it_would_refuse_if_somebody_sent_it(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $body = $document['paths']['/vaults/{vault}/contacts/{contact}/merge/{other}']['post']['requestBody'];

        $this->assertArrayNotHasKey('example', $body['content']['application/json']);
    }

    #[Test]
    public function it_carries_the_worked_example_the_reference_already_shows(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $example = $document['paths']['/vaults/{vault}']['get']['responses'][200]['content']['application/json']['example'];

        $this->assertSame('vault', $example['data']['type']);
    }

    #[Test]
    public function it_says_when_an_endpoint_answers_with_something_other_than_json(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $answer = $document['paths']['/vaults/{vault}/contacts/{contact}/vcard']['get']['responses'][200];

        $this->assertArrayHasKey('text/vcard', $answer['content']);
        $this->assertArrayNotHasKey('application/json', $answer['content']);
        $this->assertSame('string', $answer['content']['text/vcard']['schema']['type']);
    }

    #[Test]
    public function it_still_says_json_for_an_endpoint_that_declares_no_media_type(): void
    {
        $document = new BuildOpenApiDocument()->execute();

        $answer = $document['paths']['/vaults/{vault}/contacts/{contact}']['get']['responses'][200];

        $this->assertArrayHasKey('application/json', $answer['content']);
        $this->assertArrayNotHasKey('text/vcard', $answer['content']);
    }
}
