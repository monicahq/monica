<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Docs;

use App\ViewModels\Docs\PortalCatalogue;
use App\ViewModels\Docs\PortalDocsViewModel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PortalDocsViewModelTest extends TestCase
{
    #[Test]
    public function it_leads_the_breadcrumb_through_the_section_a_page_belongs_to(): void
    {
        $labels = array_column($this->viewModel('start.first-contact')->breadcrumb(), 'label');

        $this->assertSame(['Home', 'Documentation', 'Start here', 'Record your first person'], $labels);
    }

    #[Test]
    public function it_does_not_repeat_the_section_in_the_breadcrumb_of_its_own_index_page(): void
    {
        $labels = array_column($this->viewModel('start.introduction')->breadcrumb(), 'label');

        $this->assertSame(['Home', 'Documentation', 'Start here'], $labels);
    }

    #[Test]
    public function it_points_the_edit_link_at_the_file_the_page_comes_from(): void
    {
        $this->assertSame(
            'https://github.com/monicahq/monica/edit/main/docs/portal/en/2-start-here/3-record-your-first-person.md',
            $this->viewModel('start.first-contact')->editUrl(),
        );
    }

    #[Test]
    public function it_hands_an_assistant_the_page_with_its_links_resolved(): void
    {
        $markdown = $this->viewModel('start.introduction')->markdown();

        $this->assertStringStartsWith('# Start here', $markdown);
        $this->assertStringNotContainsString('@doc(', $markdown);
        $this->assertStringContainsString('[Create your account]('.route('docs.portal.show', ['locale' => 'en', 'section' => 'start-here', 'slug' => 'create-your-account']).')', $markdown);
    }

    private function viewModel(string $id): PortalDocsViewModel
    {
        $catalogue = new PortalCatalogue(locale: 'en');

        return new PortalDocsViewModel(catalogue: $catalogue, page: $catalogue->page($id));
    }
}
