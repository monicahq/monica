<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Docs;

use App\Models\User;
use App\ViewModels\Docs\ApiChangelogViewModel;
use App\ViewModels\Docs\ApiDocsViewModel;
use App\ViewModels\Docs\DocsViewModel;
use App\ViewModels\Docs\PortalCatalogue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_links_every_area_to_its_pages(): void
    {
        $urls = array_column(new DocsViewModel()->areas(), 'url', 'title');

        $this->assertSame([
            'Using Monica' => route('docs.portal.index', ['locale' => 'en']),
            'API' => route('docs.api.index'),
            'MCP' => route('docs.mcp.index'),
            'CardDAV and CalDAV' => route('docs.carddav.index'),
        ], $urls);
    }

    #[Test]
    public function it_leaves_out_what_is_switched_off_for_every_account(): void
    {
        Feature::for(null)->deactivate('carddav');
        Feature::for(null)->deactivate('mcp-server');
        Feature::flushCache();

        $viewModel = new DocsViewModel;

        $this->assertSame(['Using Monica', 'API'], array_column($viewModel->areas(), 'title'));
        $this->assertStringNotContainsString(route('docs.carddav.markdown.index'), $viewModel->llmsText());
        $this->assertStringNotContainsString(route('docs.mcp.markdown.index'), $viewModel->llmsText());
    }

    #[Test]
    public function it_shows_an_account_what_is_switched_on_for_it(): void
    {
        Feature::for(null)->deactivate('carddav');
        $jim = User::factory()->create();
        $jim->account->features()->activate('carddav');
        Feature::flushCache();

        $this->assertContains('CardDAV and CalDAV', array_column(new DocsViewModel(account: $jim->account)->areas(), 'title'));
    }

    #[Test]
    public function it_lists_every_page_of_the_reference_in_the_text_file_for_assistants(): void
    {
        $text = new DocsViewModel()->llmsText();

        $this->assertStringContainsString('('.route('docs.api.markdown.index').')', $text);
        $this->assertStringContainsString('- [MCP]('.route('docs.mcp.markdown.index').')', $text);
        $this->assertStringContainsString('[Sync your contacts and calendars]('.route('docs.carddav.markdown.index').')', $text);

        foreach (new ApiDocsViewModel()->sections() as $section) {
            $this->assertStringContainsString(
                "- [{$section->title}](".route('docs.api.markdown.show', ['section' => $section->id]).')',
                $text,
            );
        }
    }

    #[Test]
    public function it_writes_the_text_file_for_assistants_in_english_whatever_the_locale(): void
    {
        app()->setLocale('fr_FR');

        $text = new DocsViewModel()->llmsText();

        $this->assertStringStartsWith("# Monica\n", $text);
        $this->assertStringContainsString('## API reference', $text);
    }

    #[Test]
    public function it_says_whether_registration_is_enabled(): void
    {
        config()->set('monica.registration_enabled', true);

        $this->assertTrue(new DocsViewModel()->registrationIsEnabled());

        config()->set('monica.registration_enabled', false);

        $this->assertFalse(new DocsViewModel()->registrationIsEnabled());
    }

    #[Test]
    public function it_lists_the_api_changelog_and_its_entries_for_assistants_and_crawlers(): void
    {
        $this->app->instance(ApiChangelogViewModel::class, new ApiChangelogViewModel(directory: base_path('tests/Fixtures/Docs/ApiChangelog/Valid')));

        $text = new DocsViewModel()->llmsText();
        $sitemap = new DocsViewModel()->sitemap();

        $this->assertStringContainsString('- [Changelog]('.route('docs.api.changelog.markdown.index').')', $text);
        $this->assertStringContainsString('- [Adds colour to the vault object]('.route('docs.api.changelog.markdown.show', ['entry' => 'vault-colour']).')', $text);
        $this->assertStringContainsString('<loc>'.route('docs.api.changelog.index').'</loc>', $sitemap);
        $this->assertStringContainsString('<loc>'.route('docs.api.changelog.show', ['entry' => 'vault-colour']).'</loc>', $sitemap);
    }

    #[Test]
    public function it_lists_every_public_page_in_the_sitemap(): void
    {
        Feature::for(null)->activate('marketplace');
        Feature::flushCache();

        $sitemap = new DocsViewModel()->sitemap();

        $this->assertStringContainsString('<loc>'.route('docs.index').'</loc>', $sitemap);
        $this->assertStringContainsString('<loc>'.route('docs.api.index').'</loc>', $sitemap);
        $this->assertStringContainsString('<loc>'.route('docs.mcp.index').'</loc>', $sitemap);
        $this->assertStringContainsString('<loc>'.route('docs.carddav.index').'</loc>', $sitemap);
        $this->assertStringContainsString('<loc>'.route('marketplace.index').'</loc>', $sitemap);

        foreach (new ApiDocsViewModel()->sections() as $section) {
            $this->assertStringContainsString('<loc>'.route('docs.api.show', ['section' => $section->id]).'</loc>', $sitemap);
        }

        foreach (new PortalCatalogue('en')->pages() as $page) {
            $lastModified = $page->lastUpdated === null ? '' : "<lastmod>{$page->lastUpdated}</lastmod>";

            $this->assertStringContainsString("<loc>{$page->url()}</loc>{$lastModified}</url>", $sitemap);
        }
    }

    #[Test]
    public function it_leaves_out_of_the_sitemap_what_is_switched_off_for_every_account(): void
    {
        Feature::for(null)->deactivate('carddav');
        Feature::for(null)->deactivate('mcp-server');
        Feature::for(null)->deactivate('marketplace');
        Feature::flushCache();

        $sitemap = new DocsViewModel()->sitemap();

        $this->assertStringNotContainsString(route('docs.carddav.index'), $sitemap);
        $this->assertStringNotContainsString(route('docs.mcp.index'), $sitemap);
        $this->assertStringNotContainsString(route('marketplace.index'), $sitemap);
    }

    #[Test]
    public function it_points_crawlers_at_the_sitemap_and_disallows_nothing(): void
    {
        $this->assertSame(
            "User-agent: *\nDisallow:\n\nSitemap: ".route('sitemap.index')."\n",
            new DocsViewModel()->robotsText(),
        );
    }

    #[Test]
    public function it_names_no_sitemap_when_search_engines_are_kept_out(): void
    {
        config()->set('monica.search_engine_indexing', false);

        $this->assertSame("User-agent: *\nDisallow:\n", new DocsViewModel()->robotsText());
    }
}
