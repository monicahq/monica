<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Docs;

use App\ViewModels\Docs\PortalCatalogue;
use App\ViewModels\Docs\PortalMarkdown;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

class HelpGuideCoverageTest extends TestCase
{
    #[Test]
    public function every_help_bubble_points_at_a_guide_that_exists(): void
    {
        $catalogue = new PortalCatalogue(locale: 'en');
        $broken = [];

        foreach (Finder::create()->files()->in(resource_path('views'))->name('*.blade.php') as $file) {
            preg_match_all('/<x-help\b(.*?)(?<![-=])>/s', $file->getContents(), $tags);

            foreach ($tags[1] as $attributes) {
                $where = $file->getRelativePathname();

                if (! preg_match('/(?<![:\w-])guide="([^"#]+)(?:#([^"]+))?"/', $attributes, $guide)) {
                    $broken[] = "{$where}: a bubble names no guide";

                    continue;
                }

                $page = $catalogue->page($guide[1]);

                if ($page === null) {
                    $broken[] = "{$where}: no help centre page {$guide[1]}";

                    continue;
                }

                if (! isset($guide[2])) {
                    continue;
                }

                $sections = array_column(new PortalMarkdown(catalogue: $catalogue)->render(PortalMarkdown::body($page->contents()), $page->id)['contents'], 'id');

                if (! in_array($guide[2], $sections, true)) {
                    $broken[] = "{$where}: no section #{$guide[2]} in {$guide[1]}, which has ".implode(', ', $sections);
                }
            }
        }

        $this->assertSame([], $broken, 'Write the missing page or section in docs/portal with the writer skill, see the help-bubbles skill.');
    }
}
