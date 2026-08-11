<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RowTest extends TestCase
{
    #[Test]
    public function it_is_the_link_when_it_is_given_one(): void
    {
        $rendered = $this->blade('<x-row title="Templates" href="/settings/account/templates" />');

        $rendered->assertSee('<a', escape: false);
        $rendered->assertSee('href="/settings/account/templates"', escape: false);
        $rendered->assertSee('erica-row--interactive', escape: false);
    }

    #[Test]
    public function it_is_a_plain_division_when_it_goes_nowhere(): void
    {
        $rendered = $this->blade('<x-row title="Notifications" />');

        $rendered->assertSee('Notifications', escape: false);
        $rendered->assertDontSee('<a', escape: false);
        $rendered->assertDontSee('erica-row--interactive', escape: false);
    }

    #[Test]
    public function it_draws_its_mark_in_the_colour_of_what_it_holds(): void
    {
        $rendered = $this->blade('<x-row title="Profile" icon="profile" icon-color="var(--terracotta)" icon-background="var(--terracotta-tint)" />');

        $rendered->assertSee('erica-row__mark', escape: false);
        $rendered->assertSee('background: var(--terracotta-tint); color: var(--terracotta)', escape: false);
    }

    #[Test]
    public function it_draws_no_box_at_the_end_when_there_is_nothing_to_put_in_it(): void
    {
        $this->blade('<x-row title="Notifications" />')
            ->assertDontSee('erica-row__end', escape: false);

        $this->blade('<x-row title="Security and access" meta="Two-factor on" />')
            ->assertSee('erica-row__end', escape: false)
            ->assertSee('<b>Two-factor on</b>', escape: false);
    }
}
