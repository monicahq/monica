<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TabsTest extends TestCase
{
    #[Test]
    public function the_row_draws_the_rule_the_tabs_sit_on(): void
    {
        $this->blade('<x-tabs><x-tabs.item href="/general">General</x-tabs.item></x-tabs>')
            ->assertSee('role="tablist"', escape: false)
            ->assertSee('border-b border-hairline', escape: false);
    }

    #[Test]
    public function the_tab_you_are_on_thickens_its_own_piece_of_the_rule(): void
    {
        $rendered = $this->blade('<x-tabs.item href="/general" :selected="true">General</x-tabs.item>');

        $rendered->assertSee('aria-selected="true"', escape: false);
        $rendered->assertSee('aria-[selected=true]:border-b-primary', escape: false);
        $rendered->assertDontSee('rounded-full bg-primary', escape: false);
    }

    #[Test]
    public function it_is_a_link_when_the_page_has_a_url_and_a_button_otherwise(): void
    {
        $this->blade('<x-tabs.item href="/general">General</x-tabs.item>')
            ->assertSee('<a', escape: false)
            ->assertSee('href="/general"', escape: false);

        $this->blade('<x-tabs.item>General</x-tabs.item>')
            ->assertSee('<button', escape: false)
            ->assertSee('type="button"', escape: false);
    }

    #[Test]
    public function the_count_takes_a_ground_only_on_the_tab_you_are_on(): void
    {
        $this->blade('<x-tabs.item href="/general" :selected="true" :count="3" count-label="Three fields">General</x-tabs.item>')
            ->assertSee('rounded-full px-1.25 text-[11px] font-semibold bg-hover text-body', escape: false)
            ->assertSee('title="Three fields"', escape: false);

        $this->blade('<x-tabs.item href="/general" :count="3">General</x-tabs.item>')
            ->assertSee('bg-transparent text-muted-soft', escape: false);
    }

    #[Test]
    public function it_draws_no_count_when_it_is_given_none(): void
    {
        $this->blade('<x-tabs.item href="/general">General</x-tabs.item>')
            ->assertDontSee('rounded-full', escape: false);
    }

    #[Test]
    public function the_control_that_adds_a_tab_is_not_announced_as_one(): void
    {
        $rendered = $this->blade('<x-tabs.item action icon="plus">Add a page</x-tabs.item>');

        $rendered->assertSee('<button', escape: false);
        $rendered->assertDontSee('role="tab"', escape: false);
        $rendered->assertDontSee('aria-selected', escape: false);
    }
}
