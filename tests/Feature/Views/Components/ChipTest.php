<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChipTest extends TestCase
{
    #[Test]
    public function it_is_a_link_when_the_filter_has_a_url(): void
    {
        $rendered = $this->blade('<x-chip href="/settings/account/templates?filter=all">All</x-chip>');

        $rendered->assertSee('<a', escape: false);
        $rendered->assertSee('erica-chip', escape: false);
        $rendered->assertSee('All', escape: false);
        $rendered->assertDontSee('aria-pressed', escape: false);
    }

    #[Test]
    public function it_is_a_button_when_it_only_changes_something_on_the_page(): void
    {
        $rendered = $this->blade('<x-chip>Favourites</x-chip>');

        $rendered->assertSee('<button', escape: false);
        $rendered->assertSee('type="button"', escape: false);
        $rendered->assertSee('aria-pressed="false"', escape: false);
    }

    #[Test]
    public function what_is_in_force_says_so_without_relying_on_the_fill(): void
    {
        $this->blade('<x-chip href="/x" :selected="true">Mine</x-chip>')
            ->assertSee('aria-current="true"', escape: false);

        $this->blade('<x-chip :selected="true">Mine</x-chip>')
            ->assertSee('aria-pressed="true"', escape: false);
    }

    #[Test]
    public function it_may_carry_a_count_in_mono(): void
    {
        $this->blade('<x-chip href="/x" :count="24">People</x-chip>')
            ->assertSee('<span class="erica-chip__count">24</span>', escape: false);

        $this->blade('<x-chip href="/x">People</x-chip>')
            ->assertDontSee('erica-chip__count', escape: false);
    }
}
