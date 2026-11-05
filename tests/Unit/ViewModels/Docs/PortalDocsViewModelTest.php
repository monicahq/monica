<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Docs;

use App\ViewModels\Docs\PortalCatalogue;
use App\ViewModels\Docs\PortalDocsViewModel;
use App\ViewModels\Docs\PortalPage;
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

    #[Test]
    public function it_writes_the_date_a_page_was_last_updated_in_the_language_of_the_guide(): void
    {
        $viewModel = $this->viewModelOf(lastUpdated: '2026-09-29');

        $this->assertSame('2026-09-29', $viewModel->lastUpdated()?->toDateString());
        $this->assertSame('September 29, 2026', $viewModel->lastUpdatedLabel());
    }

    #[Test]
    public function it_gives_no_date_when_the_page_declares_none_or_an_impossible_one(): void
    {
        $this->assertNull($this->viewModelOf(lastUpdated: null)->lastUpdatedLabel());
        $this->assertNull($this->viewModelOf(lastUpdated: '2026-02-30')->lastUpdated());
        $this->assertNull($this->viewModelOf(lastUpdated: 'last week')->lastUpdated());
    }

    private function viewModel(string $id): PortalDocsViewModel
    {
        $catalogue = new PortalCatalogue(locale: 'en');

        return new PortalDocsViewModel(catalogue: $catalogue, page: $catalogue->page($id));
    }

    private function viewModelOf(?string $lastUpdated): PortalDocsViewModel
    {
        $page = new PortalPage(
            id: 'office.tour',
            title: 'Tour',
            description: 'Michael shows the camera crew around the Scranton branch.',
            slug: 'tour',
            section: 'office',
            locale: 'en',
            relativePath: '2-office/2-tour.md',
            path: '/nowhere/2-tour.md',
            sectionOrder: 2,
            pageOrder: 2,
            lastUpdated: $lastUpdated,
        );

        return new PortalDocsViewModel(catalogue: new PortalCatalogue(locale: 'en'), page: $page);
    }
}
