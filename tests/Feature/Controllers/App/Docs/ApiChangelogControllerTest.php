<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Docs;

use App\ViewModels\Docs\ApiChangelogViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiChangelogControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_empty_changelog_says_nothing_has_been_recorded(): void
    {
        $this->app->instance(ApiChangelogViewModel::class, new ApiChangelogViewModel(directory: base_path('tests/Fixtures/Docs/ApiChangelog/None')));

        $response = $this->get(route('docs.api.changelog.index'));

        $response->assertOk();
        $response->assertSee('Every change to the API, grouped by the date it shipped.');
        $response->assertSee('No change has been recorded yet.');
        $response->assertDontSee('Breaking changes only');
        $response->assertSee('aria-current="page"', escape: false);
    }

    #[Test]
    public function the_changelog_lists_its_entries_and_lets_a_reader_narrow_them(): void
    {
        $this->bindFixtures();

        $response = $this->get(route('docs.api.changelog.index'));

        $response->assertOk();
        $response->assertSee('Birthdates are a calendar date, not a timestamp');
        $response->assertSee(route('docs.api.changelog.show', ['entry' => 'contact-birthdate-format']), escape: false);
        $response->assertSee('datetime="2026-11-04"', escape: false);
        $response->assertSee('4 changes');
        $response->assertSee('x-data="apiChangelog"', escape: false);
        $response->assertSee('aria-label="Kind of change"', escape: false);
        $response->assertSee('Breaking changes only');
        $response->assertSee('href="#release-2026-09-16"', escape: false);
        $response->assertDontSee('No change has been recorded yet.');
    }

    #[Test]
    public function an_entry_shows_what_changed_and_no_upgrade_steps(): void
    {
        $this->bindFixtures();

        $response = $this->get(route('docs.api.changelog.show', ['entry' => 'contact-birthdate-format']));

        $response->assertOk();
        $response->assertSee('Birthdates are a calendar date, not a timestamp');
        $response->assertSee('role="note"', escape: false);
        $response->assertSee('Why this is a breaking change');
        $response->assertSee('Why it changed');
        $response->assertSee(route('docs.api.show', ['section' => 'contacts-index']), escape: false);
        $response->assertSee('In the same release');
        $response->assertSee('aria-label="Shape shown"', escape: false);
        $response->assertDontSee('Upgrade');
        $response->assertDontSee('Monica-Version');
    }

    #[Test]
    public function an_entry_names_an_endpoint_the_reference_no_longer_documents_without_a_link_and_without_an_example(): void
    {
        $this->bindFixtures();

        $response = $this->get(route('docs.api.changelog.show', ['entry' => 'profile-locale-removed']));

        $response->assertSee('/me/job-title');
        $response->assertDontSee('/me/job-title</a>', escape: false);
        $response->assertDontSee('aria-label="Example"', escape: false);
    }

    #[Test]
    public function it_finds_nothing_for_an_entry_that_does_not_exist(): void
    {
        $this->get('/docs/api/changelog/nothing-of-the-sort')->assertNotFound();
    }

    private function bindFixtures(): void
    {
        $this->app->instance(ApiChangelogViewModel::class, new ApiChangelogViewModel(directory: base_path('tests/Fixtures/Docs/ApiChangelog/Valid')));
    }
}
