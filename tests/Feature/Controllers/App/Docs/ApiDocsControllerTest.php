<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Docs;

use App\ViewModels\Docs\ApiDocsSection;
use App\ViewModels\Docs\ApiDocsViewModel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiDocsControllerTest extends TestCase
{
    #[Test]
    public function the_way_in_leads_to_the_page_the_reference_opens_with(): void
    {
        $response = $this->get(route('docs.api.index'));

        $response->assertRedirect(route('docs.api.show', ['section' => 'introduction']));
    }

    #[Test]
    public function it_shows_the_reference_to_a_visitor_who_is_not_signed_in(): void
    {
        $this->get(route('docs.api.show', ['section' => 'me-show']))->assertOk();
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

        $response->assertSee('Get your profile');
        $response->assertSee('/api/v1/me');
        $response->assertDontSee('Revokes the token you are calling with.');
    }

    #[Test]
    public function a_page_names_its_own_endpoint_in_the_title(): void
    {
        $response = $this->get(route('docs.api.show', ['section' => 'logout-destroy']));

        $response->assertSee('Log out · API reference', escape: false);
    }

    #[Test]
    public function every_page_carries_the_whole_sidebar(): void
    {
        $response = $this->get(route('docs.api.show', ['section' => 'status-show']));

        $response->assertSee('Getting started');
        $response->assertSee('Resources');
        $response->assertSee('Health');
        $response->assertSee('Your profile');
        $response->assertSee('Introduction');
        $response->assertSee('Pagination');
    }

    #[Test]
    public function the_sidebar_links_to_pages_rather_than_to_anchors(): void
    {
        $response = $this->get(route('docs.api.show', ['section' => 'status-show']));

        $response->assertSee(route('docs.api.show', ['section' => 'me-show']), escape: false);
        $response->assertDontSee('href="#', escape: false);
    }

    #[Test]
    public function the_sidebar_marks_the_page_being_read(): void
    {
        $response = $this->get(route('docs.api.show', ['section' => 'me-update']));

        $response->assertSee('aria-current="page"', escape: false);
    }

    #[Test]
    public function it_offers_a_sample_in_three_languages(): void
    {
        $response = $this->get(route('docs.api.show', ['section' => 'login-store']));

        $response->assertSee('cURL');
        $response->assertSee('JavaScript');
        $response->assertSee('PHP');
    }

    #[Test]
    public function it_sends_somebody_to_where_keys_are_issued(): void
    {
        $response = $this->get(route('docs.api.show', ['section' => 'introduction']));

        $response->assertSee('Get an API key');
        $response->assertSee(route('settings.security.index'), escape: false);
    }

    #[Test]
    public function it_says_what_the_endpoint_costs_against_the_rate_limit(): void
    {
        $response = $this->get(route('docs.api.show', ['section' => 'me-show']));

        $response->assertSee('Rate limit');
        $response->assertSee('X-RateLimit-Remaining');
    }

    #[Test]
    public function every_section_of_the_reference_is_reachable(): void
    {
        $ids = array_map(
            fn (ApiDocsSection $section): string => $section->id,
            new ApiDocsViewModel()->sections(),
        );

        $this->assertCount(11, $ids);

        foreach ($ids as $id) {
            $this->get(route('docs.api.show', ['section' => $id]))
                ->assertOk()
                ->assertViewIs('app.docs.api.show');
        }
    }
}
