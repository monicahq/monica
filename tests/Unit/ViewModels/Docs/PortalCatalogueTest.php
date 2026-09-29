<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Docs;

use App\ViewModels\Docs\PortalCatalogue;
use App\ViewModels\Docs\PortalMarkdown;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

class PortalCatalogueTest extends TestCase
{
    #[Test]
    public function it_reads_the_frontmatter_of_a_page(): void
    {
        $contents = "---\nid: office.tour\ntitle: \"Tour: the office\"\nslug: tour\nsection: office\n---\n\n# Tour: the office\n";

        $this->assertSame(
            ['id' => 'office.tour', 'title' => 'Tour: the office', 'slug' => 'tour', 'section' => 'office'],
            PortalCatalogue::frontmatter($contents),
        );
    }

    #[Test]
    public function it_refuses_frontmatter_missing_one_of_its_four_keys(): void
    {
        $this->assertNull(PortalCatalogue::frontmatter("---\nid: office.tour\ntitle: Tour\nslug: tour\n---\n"));
        $this->assertNull(PortalCatalogue::frontmatter("# No frontmatter\n"));
    }

    #[Test]
    public function it_orders_the_pages_by_the_numbers_in_front_of_their_folders_and_files(): void
    {
        $catalogue = new PortalCatalogue(locale: 'en');

        $this->assertSame(
            [
                'portal.introduction',
                'start.introduction',
                'start.create-account',
                'start.first-contact',
                'concepts.model',
                'contacts.introduction',
                'contacts.add',
                'contacts.edit',
                'contacts.list',
                'contacts.photo',
                'profiles.add',
                'relationships.introduction',
                'relationships.add',
                'relationships.edit',
                'life-events.record',
                'life-events.timeline',
                'reminders.introduction',
                'reminders.create',
                'reminders.answer',
                'reminders.how-they-work',
                'notifications.channels',
                'notifications.inbox',
                'vaults.introduction',
                'vaults.create',
                'vaults.settings',
                'vaults.delete',
                'duplicates.introduction',
                'duplicates.review',
                'duplicates.merge',
                'sharing.introduction',
                'sharing.create-link',
                'sharing.what-a-link-shows',
                'export.vcard',
                'integrations.overview',
                'templates.introduction',
                'templates.versions',
                'templates.build-your-own',
                'templates.change',
                'templates.apply-new-version',
                'templates.import-export',
                'customise.relationship-types',
                'customise.life-event-types',
                'customise.profile-types',
                'customise.value-lists',
                'marketplace.overview',
                'account.introduction',
                'signin.password',
                'signin.magic-link',
                'signin.passkey',
                'signin.provider',
                'signin.forgot-password',
                'security.two-factor',
                'security.recovery-codes',
                'security.passkeys',
                'security.password',
                'security.api-keys',
                'security.assistants',
                'security.suspicious-activity',
                'account.profile',
                'account.preferences',
                'account.logs',
                'account.delete',
                'concepts.privacy',
                'troubleshoot.introduction',
                'troubleshoot.cannot-sign-in',
                'troubleshoot.no-email',
                'troubleshoot.reminder-not-received',
                'troubleshoot.limit-reached',
                'reference.introduction',
                'reference.glossary',
                'reference.field-types',
                'reference.emails',
                'reference.limits',
            ],
            array_map(fn ($page): string => $page->id, $catalogue->pages()),
        );
    }

    #[Test]
    public function it_groups_the_pages_under_the_index_page_of_their_section(): void
    {
        $sections = new PortalCatalogue(locale: 'en')->sections();

        $this->assertSame('start.introduction', $sections[0]['index']->id);
        $this->assertSame(
            ['start.create-account', 'start.first-contact', 'concepts.model'],
            array_map(fn ($page): string => $page->id, $sections[0]['pages']),
        );
    }

    #[Test]
    public function it_finds_a_section_index_by_the_section_alone_and_a_page_by_its_slug(): void
    {
        $catalogue = new PortalCatalogue(locale: 'en');

        $this->assertSame('start.introduction', $catalogue->find(section: 'start-here')?->id);
        $this->assertSame('start.first-contact', $catalogue->find(section: 'start-here', slug: 'record-your-first-person')?->id);
        $this->assertNull($catalogue->find(section: 'start-here', slug: 'the-warehouse'));
        $this->assertNull($catalogue->find(section: 'portal', slug: 'introduction'));
    }

    #[Test]
    public function it_gives_the_pages_before_and_after_one_in_reading_order(): void
    {
        $catalogue = new PortalCatalogue(locale: 'en');
        $page = $catalogue->page('start.introduction');

        $this->assertSame('portal.introduction', $catalogue->previous($page)?->id);
        $this->assertSame('start.create-account', $catalogue->next($page)?->id);
        $this->assertNull($catalogue->previous($catalogue->root()));
    }

    #[Test]
    public function it_knows_nothing_of_a_language_without_a_folder(): void
    {
        $catalogue = new PortalCatalogue(locale: 'xx');

        $this->assertSame([], $catalogue->pages());
        $this->assertNull($catalogue->root());
    }

    #[Test]
    public function it_gives_every_file_complete_frontmatter_and_a_heading_matching_its_title(): void
    {
        $broken = [];

        foreach ($this->files() as $path => $contents) {
            $frontmatter = PortalCatalogue::frontmatter($contents);

            if ($frontmatter === null || ! str_starts_with(PortalMarkdown::body($contents), "# {$frontmatter['title']}\n")) {
                $broken[] = $path;
            }
        }

        $this->assertSame([], $broken);
    }

    #[Test]
    public function it_keeps_every_id_and_every_address_unique_within_a_language(): void
    {
        foreach (PortalCatalogue::locales() as $locale) {
            $pages = new PortalCatalogue(locale: $locale)->pages();
            $ids = array_map(fn ($page): string => $page->id, $pages);
            $addresses = array_map(fn ($page): string => $page->url(), $pages);

            $this->assertSame($ids, array_values(array_unique($ids)), "Two pages share an id in {$locale}.");
            $this->assertSame($addresses, array_values(array_unique($addresses)), "Two pages share an address in {$locale}.");
        }
    }

    #[Test]
    public function it_gives_every_section_an_index_page_and_every_language_a_root_page(): void
    {
        foreach (PortalCatalogue::locales() as $locale) {
            $catalogue = new PortalCatalogue(locale: $locale);
            $grouped = array_sum(array_map(fn (array $section): int => count($section['pages']) + 1, $catalogue->sections()));

            $this->assertNotNull($catalogue->root(), "{$locale} has no root page.");
            $this->assertSame(count($catalogue->pages()) - 1, $grouped, "A page in {$locale} sits in a section with no index page.");
        }
    }

    #[Test]
    public function it_resolves_every_link_from_one_page_to_another(): void
    {
        $broken = [];

        foreach (PortalCatalogue::locales() as $locale) {
            $catalogue = new PortalCatalogue(locale: $locale);

            foreach ($catalogue->pages() as $page) {
                foreach (PortalMarkdown::references(PortalMarkdown::body($page->contents())) as $id) {
                    if ($catalogue->page($id) === null) {
                        $broken[] = "{$locale}/{$page->relativePath} -> @doc({$id})";
                    }
                }
            }
        }

        $this->assertSame([], $broken);
    }

    #[Test]
    public function it_numbers_every_screenshot_in_page_order_and_names_it_after_the_screen_it_shows(): void
    {
        $broken = [];

        foreach (PortalCatalogue::locales() as $locale) {
            foreach (new PortalCatalogue(locale: $locale)->pages() as $page) {
                foreach (PortalMarkdown::images(PortalMarkdown::body($page->contents())) as $position => $source) {
                    $named = preg_match('/^(\d+)-([a-zA-Z.]+)(--[a-z0-9-]+)?\.webp$/', $source, $matches) === 1;

                    if (! $named || (int) $matches[1] !== $position + 1) {
                        $broken[] = "{$locale}/{$page->relativePath} -> {$source} should start with ".($position + 1).'-';
                    }

                    if (! $named || ! Route::has($matches[2])) {
                        $broken[] = "{$locale}/{$page->relativePath} -> {$source} does not name a route";
                    }

                    if (! is_file(PortalMarkdown::imageDirectory(locale: $locale, pageId: $page->id)."/{$source}")) {
                        $broken[] = "{$locale}/{$page->relativePath} -> {$source} is missing";
                    }
                }
            }
        }

        $this->assertSame([], $broken);
    }

    #[Test]
    public function it_keeps_no_screenshot_that_no_page_shows(): void
    {
        $unused = [];
        $shown = [];

        foreach (PortalCatalogue::locales() as $locale) {
            foreach (new PortalCatalogue(locale: $locale)->pages() as $page) {
                foreach (PortalMarkdown::images(PortalMarkdown::body($page->contents())) as $source) {
                    $shown[] = PortalMarkdown::imageDirectory(locale: $locale, pageId: $page->id)."/{$source}";
                }
            }
        }

        foreach (glob(public_path('images/docs/*/*/*')) ?: [] as $file) {
            if (! in_array($file, $shown, true)) {
                $unused[] = str_replace(public_path().'/', '', $file);
            }
        }

        $this->assertSame([], $unused);
    }

    /** @return array<string, string> */
    private function files(): array
    {
        $files = [];

        foreach (PortalCatalogue::locales() as $locale) {
            foreach (Finder::create()->files()->in(PortalCatalogue::directory()."/{$locale}")->name('*.md') as $file) {
                $files["{$locale}/{$file->getRelativePathname()}"] = $file->getContents();
            }
        }

        return $files;
    }
}
