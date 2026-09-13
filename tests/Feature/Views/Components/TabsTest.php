<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TabsTest extends TestCase
{
    #[Test]
    public function the_row_is_announced_as_a_list_of_tabs(): void
    {
        $this->blade('<x-tabs><x-tabs.item href="/general">General</x-tabs.item></x-tabs>')
            ->assertSee('role="tablist"', escape: false);
    }

    #[Test]
    public function the_tab_you_are_on_says_so(): void
    {
        $rendered = $this->blade('<x-tabs.item href="/general" :selected="true">General</x-tabs.item>');

        $rendered->assertSee('aria-selected="true"', escape: false);
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
    public function the_count_says_in_words_what_it_counts(): void
    {
        $this->blade('<x-tabs.item href="/general" :selected="true" :count="3" count-label="Three fields">General</x-tabs.item>')
            ->assertSee('title="Three fields"', escape: false);

        $this->blade('<x-tabs.item href="/general" :count="3">General</x-tabs.item>');
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
