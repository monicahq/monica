<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components\ApiDocs;

use App\ViewModels\Docs\ApiDocsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SidebarTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_carries_the_two_headings_of_the_reference(): void
    {
        $this->blade('<x-api-docs.sidebar :navigation="$navigation" :current="$current" />', [
            'navigation' => new ApiDocsViewModel()->navigation(),
            'current' => 'me-show',
        ])
            ->assertSee('Getting started')
            ->assertSee('Resources');
    }

    #[Test]
    public function every_entry_is_a_link_to_a_page(): void
    {
        $this->blade('<x-api-docs.sidebar :navigation="$navigation" :current="$current" />', [
            'navigation' => new ApiDocsViewModel()->navigation(),
            'current' => 'me-show',
        ])
            ->assertSee(route('docs.api.show', ['section' => 'introduction']), escape: false)
            ->assertSee(route('docs.api.show', ['section' => 'me-show']), escape: false)
            ->assertDontSee('href="#', escape: false);
    }

    #[Test]
    public function the_entry_on_screen_says_so_to_a_screen_reader(): void
    {
        $this->blade('<x-api-docs.sidebar :navigation="$navigation" :current="$current" />', [
            'navigation' => new ApiDocsViewModel()->navigation(),
            'current' => 'me-show',
        ])->assertSee('aria-current', escape: false);
    }

    #[Test]
    public function each_group_of_links_is_named(): void
    {
        $this->blade('<x-api-docs.sidebar :navigation="$navigation" :current="$current" />', [
            'navigation' => new ApiDocsViewModel()->navigation(),
            'current' => 'me-show',
        ])
            ->assertSee('aria-label="Getting started"', escape: false)
            ->assertSee('aria-label="Your profile"', escape: false);
    }
}
