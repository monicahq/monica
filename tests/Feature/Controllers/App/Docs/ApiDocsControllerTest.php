<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Docs;

use App\Models\Administrator;
use App\Models\User;
use App\ViewModels\Docs\ApiDocsSection;
use App\ViewModels\Docs\ApiDocsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiDocsControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_way_in_leads_to_the_page_the_reference_opens_with(): void
    {
        $response = $this->get(route('docs.api.index'));

        $response->assertRedirect(route('docs.api.show', ['section' => 'introduction']));
    }

    #[Test]
    public function it_finds_nothing_for_a_page_that_does_not_exist(): void
    {
        $this->get('/docs/api/nothing-of-the-sort')->assertNotFound();
    }

    #[Test]
    public function a_page_carries_only_its_own_endpoint(): void
    {
        $response = $this->get(route('docs.api.show', ['section' => 'me-show']));

        $response->assertSee('/api/v1/me');
    }

    #[Test]
    public function it_keeps_where_the_reader_had_scrolled_the_menu_to(): void
    {
        $response = $this->get(route('docs.api.show', ['section' => 'vaults-index']));

        $response->assertSee('x-data="apiDocsSidebarScroll"', escape: false);
        $response->assertSee('@click="remember()"', escape: false);
    }

    #[Test]
    public function every_section_of_the_reference_is_reachable(): void
    {
        $ids = array_map(
            fn (ApiDocsSection $section): string => $section->id,
            new ApiDocsViewModel()->sections(),
        );

        $this->assertCount(330, $ids);

        $this->get(route('docs.api.show', ['section' => 'knowledge-folders-index']))
            ->assertSee('Knowledge folders')
            ->assertSee('Knowledge notes')
            ->assertSee('Knowledge lists')
            ->assertSee('Possession valuations')
            ->assertSee('Possession provenance')
            ->assertSee('Possession locations')
            ->assertSee('Possession loans')
            ->assertSee('What’s new')
            ->assertSee('Agent feedback');

        foreach (new ApiDocsViewModel()->sections() as $section) {
            $this->blade('<x-api-docs.endpoint :section="$section" />', ['section' => $section])
                ->assertSee($section->title);
        }

        foreach (['vaults-index', 'contacts-name-update', 'possessions-index'] as $id) {
            $this->get(route('docs.api.show', ['section' => $id]))
                ->assertOk()
                ->assertViewIs('app.docs.api.show');
        }

        $this->get(route('docs.api.show', ['section' => 'possessions-index']));
        $this->get(route('docs.api.show', ['section' => 'support-requests-index']))->assertSee('List support requests');
        $this->get(route('docs.api.show', ['section' => 'admin-support-requests-index']))->assertSee('List support requests of the instance');
    }

    #[Test]
    public function a_page_reading_records_says_it_also_answers_query(): void
    {
        $response = $this->get(route('docs.api.show', ['section' => 'contacts-index']));

        $response->assertSee('This endpoint also answers QUERY');
        $response->assertSee(route('docs.api.show', ['section' => 'query-method']), escape: false);
    }

    #[Test]
    public function a_page_writing_records_says_nothing_about_query(): void
    {
        $this->get(route('docs.api.show', ['section' => 'contacts-create']))
            ->assertDontSee('This endpoint also answers QUERY');
    }

    #[Test]
    public function the_query_guide_shows_a_request_sent_with_query(): void
    {
        $this->get(route('docs.api.show', ['section' => 'query-method']))
            ->assertOk()
            ->assertSee('The QUERY method')
            ->assertSee('-X QUERY');
    }

    #[Test]
    public function a_page_ends_with_its_neighbours_and_the_footer(): void
    {
        Administrator::factory()->create(['user_id' => User::factory()->create()->id]);

        $response = $this->get(route('docs.api.show', ['section' => 'rate-limits']));

        $response->assertSee('Authentication');
        $response->assertSee(route('docs.api.show', ['section' => 'authentication-guide']), escape: false);
        $response->assertSee('Pagination');
        $response->assertSee(route('docs.api.show', ['section' => 'pagination']), escape: false);
        $response->assertSee(route('llmsText.index'), escape: false);
        $response->assertSee(route('docs.api.changelog.index'), escape: false);
        $response->assertSee('Read the changelog');
        $response->assertSee(route('support.requests.new'), escape: false);
        $response->assertSee('datetime="2026-11-22"', escape: false);
    }
}
