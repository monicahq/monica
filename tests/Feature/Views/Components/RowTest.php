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
        $rendered->assertSee('cursor-pointer hover:bg-hover', escape: false);
    }

    #[Test]
    public function it_is_a_plain_division_when_it_goes_nowhere(): void
    {
        $rendered = $this->blade('<x-row title="Notifications" />');

        $rendered->assertSee('Notifications', escape: false);
        $rendered->assertDontSee('<a', escape: false);
        $rendered->assertDontSee('cursor-pointer hover:bg-hover', escape: false);
    }

    #[Test]
    public function it_draws_a_mark_when_it_is_given_one(): void
    {
        $rendered = $this->blade('<x-row title="Profile" icon="profile" />');

        $rendered->assertSee('<span class="flex size-8 shrink-0 items-center justify-center rounded-md border border-hairline bg-card text-body">', escape: false);
    }

    #[Test]
    public function it_draws_no_box_at_the_end_when_there_is_nothing_to_put_in_it(): void
    {
        $this->blade('<x-row title="Notifications" />')
            ->assertDontSee('ml-auto flex shrink-0 items-center', escape: false);

        $this->blade('<x-row title="Security and access" meta="Two-factor on" />')
            ->assertSee('ml-auto flex shrink-0 items-center', escape: false)
            ->assertSee('>Two-factor on</b>', escape: false);
    }
}
