<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Docs;

use App\ViewModels\Docs\ApiChangelogEntry;
use App\ViewModels\Docs\ApiChangelogViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiChangelogViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_shipped_changelog_has_no_problem(): void
    {
        $this->assertSame([], new ApiChangelogViewModel()->problems());
    }

    #[Test]
    public function it_lists_releases_newest_first_and_marks_the_newest(): void
    {
        $releases = $this->valid()->releases();

        $this->assertSame(['2026-11-04', '2026-09-16'], array_map(fn (array $release): string => $release['date']->toDateString(), $releases));
        $this->assertTrue($releases[0]['latest']);
        $this->assertFalse($releases[1]['latest']);
        $this->assertSame('One breaking change, in a field read every day.', $releases[0]['note']);
        $this->assertNull($releases[1]['note']);
        $this->assertSame(4, $releases[0]['count']);
    }

    #[Test]
    public function it_groups_entries_in_the_order_of_the_sidebar(): void
    {
        $groups = $this->valid()->releases()[0]['groups'];

        $this->assertSame(['Your profile', 'Contacts', 'Groups'], array_column($groups, 'name'));
        $this->assertCount(2, $groups[1]['entries']);
    }

    #[Test]
    public function it_hides_an_entry_whose_part_of_the_api_is_hidden(): void
    {
        Feature::for(null)->deactivate('groups');
        Feature::flushCache();

        $viewModel = $this->valid();

        $this->assertNull($viewModel->entry('group-motto'));
        $this->assertNotContains('Groups', array_column($viewModel->releases()[0]['groups'], 'name'));
    }

    #[Test]
    public function it_drops_a_release_left_with_no_entry(): void
    {
        $directory = base_path('tests/Fixtures/Docs/ApiChangelog/Hidden');

        $this->assertCount(1, new ApiChangelogViewModel(directory: $directory)->releases());

        Feature::for(null)->deactivate('groups');
        Feature::flushCache();

        $this->assertSame([], new ApiChangelogViewModel(directory: $directory)->releases());
    }

    #[Test]
    public function it_finds_an_entry_and_the_others_of_its_release(): void
    {
        $viewModel = $this->valid();
        $entry = $viewModel->entry('contact-list-filter');

        $this->assertInstanceOf(ApiChangelogEntry::class, $entry);
        $this->assertSame('2026-11-04', $entry->date->toDateString());
        $this->assertSame(
            ['profile-locale-removed', 'contact-birthdate-format', 'group-motto'],
            array_map(fn (ApiChangelogEntry $sibling): string => $sibling->id, $viewModel->sameRelease($entry)),
        );
        $this->assertSame([], $viewModel->sameRelease($viewModel->entry('vault-colour')));
        $this->assertNull($viewModel->entry('nothing-of-the-sort'));
    }

    #[Test]
    public function it_counts_the_endpoints_an_entry_touches(): void
    {
        $viewModel = $this->valid();

        $this->assertSame('2 endpoints', $viewModel->entry('contact-birthdate-format')->endpointCount());
        $this->assertSame('1 endpoint', $viewModel->entry('contact-list-filter')->endpointCount());
        $this->assertSame('All endpoints', $viewModel->entry('group-motto')->endpointCount());
    }

    #[Test]
    public function it_names_every_defect_of_a_malformed_release(): void
    {
        $problems = new ApiChangelogViewModel(directory: base_path('tests/Fixtures/Docs/ApiChangelog/Invalid'))->problems();

        $this->assertSame([
            '2026-01-01.php, entry no-summary: summary is missing.',
            '2026-01-01.php, entry unknown-kind: the kind must be Added, Changed, Deprecated or Removed.',
            '2026-01-01.php, entry unknown-group: the group must be the name of a file in resources/docs/api.',
            '2026-01-01.php, entry no-impact: a breaking entry needs an impact.',
            '2026-01-01.php, entry no-summary: another entry already uses this id.',
            '2026-02-02.php: the date must equal the file name.',
            '2026-02-02.php: a release needs at least one entry.',
        ], $problems);
    }

    #[Test]
    public function it_finds_no_problem_in_a_well_formed_release(): void
    {
        $this->assertSame([], $this->valid()->problems());
    }

    #[Test]
    public function it_writes_the_list_as_markdown(): void
    {
        $markdown = $this->valid()->markdown();

        $this->assertStringContainsString('## 4 November 2026 (latest)', $markdown);
        $this->assertStringContainsString('## 16 September 2026', $markdown);
        $this->assertStringContainsString('- Changed: [Birthdates are a calendar date, not a timestamp]('.route('docs.api.changelog.markdown.show', ['entry' => 'contact-birthdate-format']).'). Breaking.', $markdown);
    }

    #[Test]
    public function it_writes_an_empty_list_as_markdown(): void
    {
        $markdown = new ApiChangelogViewModel(directory: base_path('tests/Fixtures/Docs/ApiChangelog/None'))->markdown();

        $this->assertStringContainsString('No change has been recorded yet.', $markdown);
    }

    #[Test]
    public function it_writes_an_entry_as_markdown_without_an_upgrade_section(): void
    {
        $viewModel = $this->valid();
        $markdown = $viewModel->markdown($viewModel->entry('contact-birthdate-format'));

        $this->assertStringContainsString('# Birthdates are a calendar date, not a timestamp', $markdown);
        $this->assertStringContainsString('## Why this is a breaking change', $markdown);
        $this->assertStringContainsString('## Why it changed', $markdown);
        $this->assertStringContainsString('- `birthdate` (Changed): `GET /api/v1/vaults/{vault}/contacts`, `GET /api/v1/vaults/{vault}/contacts/{contact}`', $markdown);
        $this->assertStringContainsString('## In the same release', $markdown);
        $this->assertStringContainsString('"birthdate": "1964-03-15T00:00:00Z"', $markdown);
        $this->assertStringNotContainsString('Upgrade', $markdown);
    }

    private function valid(): ApiChangelogViewModel
    {
        return new ApiChangelogViewModel(directory: base_path('tests/Fixtures/Docs/ApiChangelog/Valid'));
    }
}
