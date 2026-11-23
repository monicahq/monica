<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Docs;

use App\Models\Administrator;
use App\Models\User;
use App\ViewModels\Docs\ApiDocsCodeSamples;
use App\ViewModels\Docs\ApiDocsSection;
use App\ViewModels\Docs\ApiDocsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiDocsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_documents_every_registered_route(): void
    {
        $viewModel = new ApiDocsViewModel;

        $documented = $viewModel->documentedRoutes();
        $missing = [];

        foreach ($viewModel->registeredRoutes() as $route) {
            if (! in_array($route, $documented, true)) {
                $missing[] = $route['method'].' '.$route['path'];
            }
        }

        $this->assertEmpty(
            $missing,
            'These routes exist but nothing in resources/docs/api documents them: '.implode(', ', $missing),
        );
    }

    #[Test]
    public function it_registers_every_documented_route(): void
    {
        $viewModel = new ApiDocsViewModel;

        $registered = $viewModel->registeredRoutes();
        $absent = [];

        foreach ($viewModel->documentedRoutes() as $route) {
            if (! in_array($route, $registered, true)) {
                $absent[] = $route['method'].' '.$route['path'];
            }
        }

        $this->assertEmpty(
            $absent,
            'These endpoints are documented but no route answers them: '.implode(', ', $absent),
        );
    }

    #[Test]
    public function it_finds_a_section_by_its_id(): void
    {
        $section = new ApiDocsViewModel()->section('me-show');

        $this->assertInstanceOf(ApiDocsSection::class, $section);
        $this->assertEquals('GET', $section->method);
        $this->assertEquals('/me', $section->path);
    }

    #[Test]
    public function it_leaves_out_an_endpoint_switched_off_for_every_account(): void
    {
        $this->assertNotNull(new ApiDocsViewModel()->section('contacts-vcard'));

        Feature::for(null)->deactivate('vcard-export');
        Feature::flushCache();

        $this->assertNull(new ApiDocsViewModel()->section('contacts-vcard'));
    }

    #[Test]
    public function it_leaves_out_a_resource_whose_every_endpoint_is_switched_off(): void
    {
        $this->assertContains('Groups', array_column(new ApiDocsViewModel()->navigation()['resources'], 'name'));

        Feature::for(null)->deactivate('groups');
        Feature::flushCache();

        $this->assertNotContains('Groups', array_column(new ApiDocsViewModel()->navigation()['resources'], 'name'));
    }

    #[Test]
    public function it_leaves_out_a_section_while_any_of_its_flags_is_off(): void
    {
        $this->assertNotNull(new ApiDocsViewModel()->section('group-files-index'));

        Feature::for(null)->deactivate('file-upload');
        Feature::flushCache();

        $this->assertNull(new ApiDocsViewModel()->section('group-files-index'));
        $this->assertNotNull(new ApiDocsViewModel()->section('group-notes-index'));
    }

    #[Test]
    public function it_finds_nothing_for_an_unknown_id(): void
    {
        $this->assertNull(new ApiDocsViewModel()->section('nothing-of-the-sort'));
    }

    #[Test]
    public function it_gives_every_get_section_its_query_section(): void
    {
        $sections = ApiDocsViewModel::withQuerySections($this->employeeSections());

        $this->assertSame(['employees-index', 'employees-create'], array_column($sections, 'id'));
        $this->assertSame('employees-index-query', $sections[0]['querySection']['id']);
        $this->assertSame('QUERY', $sections[0]['querySection']['method']);
        $this->assertSame('List employees with QUERY', $sections[0]['querySection']['title']);
        $this->assertArrayNotHasKey('querySection', $sections[1]);
    }

    #[Test]
    public function it_sends_the_query_parameters_in_the_body_of_the_query_section(): void
    {
        $query = ApiDocsViewModel::withQuerySections($this->employeeSections())[0]['querySection'];
        $section = new ApiDocsSection($query);

        $this->assertSame([], $query['queryParams']);
        $this->assertSame('branch', $query['bodyParams'][0]['name']);
        $this->assertSame('company', $query['pathParams'][0]['name']);
        $this->assertSame(['branch' => 'Scranton'], $section->exampleBody());
        $this->assertSame('Parameters', $section->paramsTitle);
    }

    #[Test]
    public function it_puts_the_example_query_of_a_get_request_in_its_address_and_sends_no_body(): void
    {
        $sections = $this->employeeSections();
        $sections[0]['pathParams'][0]['example'] = 'dunder-mifflin';

        $curl = new ApiDocsSection($sections[0])->samples()['curl']['code'];

        $this->assertStringContainsString('/employees?branch=Scranton', $curl);
        $this->assertStringNotContainsString('-d', $curl);
        $this->assertStringNotContainsString('dunder-mifflin', $curl);
    }

    #[Test]
    public function it_sends_the_example_query_of_a_query_request_in_its_body_only(): void
    {
        $curl = new ApiDocsSection(ApiDocsViewModel::withQuerySections($this->employeeSections())[0]['querySection'])->samples()['curl']['code'];

        $this->assertStringContainsString('-X QUERY', $curl);
        $this->assertStringContainsString('"branch": "Scranton"', $curl);
        $this->assertStringNotContainsString('?branch=', $curl);
    }

    #[Test]
    public function it_adds_the_refusal_of_a_parameter_sent_twice_to_the_status_codes_of_query_only(): void
    {
        $sections = $this->employeeSections();

        $page = ApiDocsViewModel::withQuerySections($sections)[0];

        $this->assertSame([200], array_column($page['statusCodes'], 'status'));
        $this->assertSame([200, 422], array_column($page['querySection']['statusCodes'], 'status'));
        $this->assertSame('With QUERY, a parameter was sent both in the address and in the body.', $page['querySection']['statusCodes'][1]['when']);
        $this->assertSame([200, 422], array_column(new ApiDocsSection($page)->statusCodes, 'status'));

        $sections[0]['statusCodes'][] = ['status' => 422, 'name' => 'Unprocessable Content', 'when' => 'The branch does not exist.', 'body' => 'A message.'];

        $extended = ApiDocsViewModel::withQuerySections($sections)[0]['querySection']['statusCodes'];

        $this->assertSame([200, 422], array_column($extended, 'status'));
        $this->assertSame('The branch does not exist. Or, with QUERY, a parameter was sent both in the address and in the body.', $extended[1]['when']);
    }

    #[Test]
    public function it_adds_no_refusal_when_the_endpoint_takes_no_query_parameters(): void
    {
        $sections = $this->employeeSections();
        unset($sections[0]['queryParams']);

        $query = ApiDocsViewModel::withQuerySections($sections)[0]['querySection'];

        $this->assertSame([200], array_column($query['statusCodes'], 'status'));
        $this->assertSame([], $query['bodyParams']);
    }

    #[Test]
    public function it_derives_no_query_section_in_a_guide(): void
    {
        foreach (new ApiDocsViewModel()->groups() as $group) {
            if (! $group['guide']) {
                continue;
            }

            foreach ($group['sections'] as $section) {
                $this->assertNull($section->querySection, $section->id);
            }
        }
    }

    #[Test]
    public function it_gives_every_section_a_unique_id(): void
    {
        $ids = array_map(fn (ApiDocsSection $section): string => $section->id, new ApiDocsViewModel()->sections());

        $this->assertEquals($ids, array_unique($ids));
    }

    #[Test]
    public function it_names_the_parameters_after_where_they_are_sent(): void
    {
        $body = new ApiDocsSection([
            'id' => 'thing-create',
            'title' => 'Create a thing',
            'method' => 'POST',
            'path' => '/things',
            'description' => 'Makes one.',
            'rateLimit' => 'Sixty requests per minute, per token.',
            'bodyParams' => [
                ['name' => 'name', 'type' => 'string', 'required' => true, 'description' => 'Its name.', 'example' => 'Dundie'],
            ],
        ]);

        $query = new ApiDocsSection([
            'id' => 'thing-index',
            'title' => 'List things',
            'method' => 'GET',
            'path' => '/things',
            'description' => 'Lists them.',
            'rateLimit' => 'Sixty requests per minute, per token.',
            'queryParams' => [
                ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Which page.'],
            ],
        ]);

        $this->assertTrue($body->hasParams());
        $this->assertEquals(['name' => 'Dundie'], $body->exampleBody());
        $this->assertEquals([], $query->exampleBody());
    }

    #[Test]
    public function it_builds_a_sample_in_three_languages(): void
    {
        $samples = new ApiDocsViewModel()->section('me-show')->samples();

        $this->assertEquals(['curl', 'javascript', 'php'], array_keys($samples));
        $this->assertStringContainsString('curl', $samples['curl']['code']);
        $this->assertStringContainsString('fetch', $samples['javascript']['code']);
        $this->assertStringContainsString('Http::withToken', $samples['php']['code']);
    }

    #[Test]
    public function it_leaves_the_authorization_header_out_of_a_public_endpoint(): void
    {
        $samples = new ApiDocsViewModel()->section('status-show')->samples();

        $this->assertStringNotContainsString('Authorization', $samples['curl']['code']);
        $this->assertStringNotContainsString('Authorization', $samples['javascript']['code']);
        $this->assertStringNotContainsString('withToken', $samples['php']['code']);
    }

    #[Test]
    public function it_reads_no_body_back_from_an_endpoint_that_sends_none(): void
    {
        $samples = new ApiDocsViewModel()->section('logout-destroy')->samples();

        $this->assertStringNotContainsString('response.json()', $samples['javascript']['code']);
        $this->assertEquals([], new ApiDocsViewModel()->section('logout-destroy')->responseLines());
    }

    #[Test]
    public function it_colours_the_quoted_parts_of_a_line(): void
    {
        $highlighted = ApiDocsCodeSamples::highlight('name: "Dundie"');

        $this->assertEquals('name: ', $highlighted['parts'][0]['text']);
        $this->assertEquals('ink', $highlighted['parts'][0]['colour']);
        $this->assertEquals('"Dundie"', $highlighted['parts'][1]['text']);
        $this->assertEquals('brand', $highlighted['parts'][1]['colour']);
    }

    #[Test]
    public function it_leaves_out_support_while_support_is_switched_off(): void
    {
        $this->assertNotNull(new ApiDocsViewModel()->section('support-requests-index'));

        config(['monica.support_enabled' => false]);

        $this->assertNull(new ApiDocsViewModel()->section('support-requests-index'));
        $this->assertNotContains('Support requests', array_column(new ApiDocsViewModel()->navigation()['resources'], 'name'));
    }

    #[Test]
    public function it_leads_the_last_guide_to_the_changelog(): void
    {
        $guides = new ApiDocsViewModel()->navigation()['guides'];

        $this->assertSame(route('docs.api.changelog.index'), end($guides)['url']);
        $this->assertSame(route('docs.api.show', ['section' => 'introduction']), $guides[0]['url']);
    }

    #[Test]
    public function it_gives_the_support_page_while_an_active_administrator_can_answer(): void
    {
        Administrator::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->assertEquals(route('support.requests.new'), new ApiDocsViewModel()->supportUrl());

        config(['monica.support_enabled' => false]);

        $this->assertNull(new ApiDocsViewModel()->supportUrl());
    }

    #[Test]
    public function it_gives_no_support_page_while_no_active_administrator_can_answer(): void
    {
        $this->assertNull(new ApiDocsViewModel()->supportUrl());

        Administrator::factory()->create(['user_id' => User::factory()->inactive()->create()->id]);

        $this->assertNull(new ApiDocsViewModel()->supportUrl());
    }

    #[Test]
    public function it_has_no_next_page_after_the_last_one(): void
    {
        $viewModel = new ApiDocsViewModel;
        $sections = $viewModel->sections();
        $last = $sections[count($sections) - 1];

        $this->assertNull($viewModel->next($last));
        $this->assertEquals($sections[count($sections) - 2]->id, $viewModel->previous($last)->id);
    }

    #[Test]
    public function it_parses_the_date_the_reference_was_last_generated(): void
    {
        $date = new ApiDocsViewModel()->lastGenerated();

        $this->assertEquals(ApiDocsViewModel::LAST_GENERATED, $date->toDateString());
    }

    #[Test]
    public function it_skips_a_page_switched_off_when_naming_the_neighbours(): void
    {
        $viewModel = new ApiDocsViewModel;
        $ids = array_map(fn (ApiDocsSection $section): string => $section->id, $viewModel->sections());
        $position = array_search('group-files-index', $ids, true);

        $this->assertEquals('group-files-index', $viewModel->next($viewModel->section($ids[$position - 1]))->id);

        Feature::for(null)->deactivate('file-upload');
        Feature::flushCache();

        $viewModel = new ApiDocsViewModel;

        $this->assertNotEquals('group-files-index', $viewModel->next($viewModel->section($ids[$position - 1]))->id);
    }

    #[Test]
    public function it_finds_the_section_documenting_an_endpoint(): void
    {
        $viewModel = new ApiDocsViewModel;

        $this->assertSame('me-show', $viewModel->sectionFor('GET', '/me')?->id);
        $this->assertNull($viewModel->sectionFor('PUT', '/me/job-title'));
    }

    #[Test]
    public function it_describes_get_and_query_on_one_page(): void
    {
        $viewModel = new ApiDocsViewModel;
        $profile = collect($viewModel->navigation()['resources'])->firstWhere('name', 'Your profile');

        $this->assertSame(['GET', 'QUERY'], collect($profile['items'])->firstWhere('id', 'me-show')['methods']);
        $this->assertNull($viewModel->section('me-show-query'));
        $this->assertSame('me-show', $viewModel->sectionFor('QUERY', '/me')?->id);
        $this->assertStringContainsString('-X QUERY', $viewModel->section('me-show')->querySection->samples()['curl']['code']);
    }

    #[Test]
    public function it_names_both_neighbours_of_a_page_in_the_middle(): void
    {
        $viewModel = new ApiDocsViewModel;

        $section = $viewModel->section('rate-limits');

        $this->assertEquals('authentication-guide', $viewModel->previous($section)->id);
        $this->assertEquals('pagination', $viewModel->next($section)->id);
    }

    #[Test]
    public function it_has_no_previous_page_before_the_first_one(): void
    {
        $viewModel = new ApiDocsViewModel;
        $first = $viewModel->sections()[0];

        $this->assertNull($viewModel->previous($first));
        $this->assertEquals('authentication-guide', $viewModel->next($first)->id);
    }

    /** @return list<array<string, mixed>> */
    private function employeeSections(): array
    {
        return [
            [
                'id' => 'employees-index',
                'title' => 'List employees',
                'label' => 'Employees',
                'method' => 'GET',
                'path' => '/companies/{company}/employees',
                'description' => 'Lists the employees of a branch.',
                'rateLimit' => 'Sixty requests per minute, per token.',
                'pathParams' => [
                    ['name' => 'company', 'type' => 'string', 'required' => true, 'description' => 'The company.'],
                ],
                'queryParams' => [
                    ['name' => 'branch', 'type' => 'string', 'required' => false, 'description' => 'The branch.', 'example' => 'Scranton'],
                ],
                'statusCodes' => [
                    ['status' => 200, 'name' => 'OK', 'when' => 'The request worked.', 'body' => 'The employees.'],
                ],
            ],
            [
                'id' => 'employees-create',
                'title' => 'Hire an employee',
                'method' => 'POST',
                'path' => '/companies/{company}/employees',
                'description' => 'Hires somebody.',
                'rateLimit' => 'Sixty requests per minute, per token.',
            ],
        ];
    }
}
