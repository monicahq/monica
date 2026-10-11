<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Docs;

use App\Models\User;
use App\ViewModels\Docs\ApiDocsViewModel;
use App\ViewModels\Docs\DocsViewModel;
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
}
