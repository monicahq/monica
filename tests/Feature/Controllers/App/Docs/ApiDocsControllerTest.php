<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Docs;

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

        $this->assertCount(307, $ids);

        $this->get(route('docs.api.show', ['section' => 'knowledge-folders-index']))
            ->assertSee('Knowledge folders')
            ->assertSee('Knowledge notes')
            ->assertSee('Knowledge lists')
            ->assertSee('Possession valuations')
            ->assertSee('Possession provenance')
            ->assertSee('Possession locations')
            ->assertSee('Possession loans');

        foreach ($ids as $id) {
            $this->get(route('docs.api.show', ['section' => $id]))
                ->assertOk()
                ->assertViewIs('app.docs.api.show');
        }

        $this->get(route('docs.api.show', ['section' => 'possessions-index']));
        $this->get(route('docs.api.show', ['section' => 'support-requests-index']))->assertSee('List support requests');
        $this->get(route('docs.api.show', ['section' => 'admin-support-requests-index']))->assertSee('List support requests of the instance');
    }

    #[Test]
    public function a_page_ends_with_its_neighbours_and_the_footer(): void
    {
        $response = $this->get(route('docs.api.show', ['section' => 'rate-limits']));

        $response->assertSee('Authentication');
        $response->assertSee(route('docs.api.show', ['section' => 'authentication-guide']), escape: false);
        $response->assertSee('Pagination');
        $response->assertSee(route('docs.api.show', ['section' => 'pagination']), escape: false);
        $response->assertSee(route('llmsText.index'), escape: false);
        $response->assertSee(route('docs.api.changelog.index'), escape: false);
        $response->assertSee('Read the changelog');
        $response->assertSee(route('support.requests.new'), escape: false);
        $response->assertSee('datetime="2026-11-12"', escape: false);
    }
}
