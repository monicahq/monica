<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Docs;

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
    public function it_splits_the_sidebar_between_guides_and_resources(): void
    {
        $navigation = new ApiDocsViewModel()->navigation();

        $this->assertEquals(
            [
                'Introduction', 'Authentication', 'Rate limits', 'Pagination', 'Errors', 'Changelog',
            ],
            array_column($navigation['guides'], 'label'),
        );

        $this->assertEquals(
            ['Authentication', 'Your profile', 'API keys', 'Vaults', 'Contacts', 'Kinds of record', 'Logs', 'Emails sent', 'Health', 'Templates', 'Billing', 'Duplicates and merges', 'Notifications', 'Notification channels', 'Relationship categories', 'Relationship types', 'Life event categories', 'Life event types', 'Reminders', 'Search', 'Life events', 'Relationships', 'MCP history', 'Imports', 'Exports', 'Groups', 'Group notes', 'Group activities', 'Group reminders', 'Group files', 'Support requests', 'Support administration'],
            array_column($navigation['resources'], 'name'),
        );
    }

    #[Test]
    public function it_gives_every_resource_entry_a_verb(): void
    {
        $navigation = new ApiDocsViewModel()->navigation();

        foreach ($navigation['resources'] as $group) {
            foreach ($group['items'] as $item) {
                $this->assertContains($item['method'], ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']);
            }
        }
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
    public function it_leads_the_last_guide_to_the_changelog(): void
    {
        $guides = new ApiDocsViewModel()->navigation()['guides'];

        $this->assertSame(route('docs.api.changelog.index'), end($guides)['url']);
        $this->assertSame(route('docs.api.show', ['section' => 'introduction']), $guides[0]['url']);
    }

    #[Test]
    public function it_finds_the_section_documenting_an_endpoint(): void
    {
        $viewModel = new ApiDocsViewModel;

        $this->assertSame('me-show', $viewModel->sectionFor('GET', '/me')?->id);
        $this->assertNull($viewModel->sectionFor('PUT', '/me/job-title'));
    }

    #[Test]
    public function it_finds_nothing_for_an_unknown_id(): void
    {
        $this->assertNull(new ApiDocsViewModel()->section('nothing-of-the-sort'));
    }

    #[Test]
    public function it_gives_every_section_a_unique_id(): void
    {
        $ids = array_map(fn (ApiDocsSection $section): string => $section->id, new ApiDocsViewModel()->sections());

        $this->assertEquals($ids, array_unique($ids));
    }

    #[Test]
    public function it_falls_back_to_the_title_for_the_sidebar_label(): void
    {
        $section = new ApiDocsSection([
            'id' => 'thing-show',
            'title' => 'Read a thing',
            'method' => 'GET',
            'path' => '/things',
            'description' => 'Reads it.',
            'rateLimit' => 'Sixty requests per minute, per token.',
        ]);

        $this->assertEquals('Read a thing', $section->label);
        $this->assertEquals('/things', $section->examplePath);
        $this->assertTrue($section->auth);
        $this->assertEquals(200, $section->responseStatus);
        $this->assertEquals('Parameters', $section->paramsTitle);
        $this->assertFalse($section->hasParams());
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

        $this->assertEquals('Body parameters', $body->paramsTitle);
        $this->assertEquals('Query parameters', $query->paramsTitle);
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
    public function it_points_the_base_url_at_the_versioned_api(): void
    {
        $this->assertStringEndsWith('/api/v1', ApiDocsViewModel::baseUrl());
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
    public function it_has_no_previous_page_before_the_first_one(): void
    {
        $viewModel = new ApiDocsViewModel;
        $first = $viewModel->sections()[0];

        $this->assertNull($viewModel->previous($first));
        $this->assertEquals('authentication-guide', $viewModel->next($first)->id);
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
    public function it_names_both_neighbours_of_a_page_in_the_middle(): void
    {
        $viewModel = new ApiDocsViewModel;

        $section = $viewModel->section('rate-limits');

        $this->assertEquals('authentication-guide', $viewModel->previous($section)->id);
        $this->assertEquals('pagination', $viewModel->next($section)->id);
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
    public function it_parses_the_date_the_reference_was_last_generated(): void
    {
        $date = new ApiDocsViewModel()->lastGenerated();

        $this->assertEquals(ApiDocsViewModel::LAST_GENERATED, $date->toDateString());
    }

    #[Test]
    public function it_gives_the_support_page_only_while_support_is_switched_on(): void
    {
        $this->assertEquals(route('support.requests.new'), new ApiDocsViewModel()->supportUrl());

        config(['monica.support_enabled' => false]);

        $this->assertNull(new ApiDocsViewModel()->supportUrl());
    }
}
