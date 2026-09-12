<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageHeaderTest extends TestCase
{
    #[Test]
    public function it_names_the_screen(): void
    {
        $this->blade('<x-page-header title="Security and access" />')
            ->assertSee('<header', escape: false)
            ->assertSee('text-page-title text-balance text-ink', escape: false)
            ->assertSee('Security and access', escape: false);
    }

    #[Test]
    public function the_line_under_the_title_is_held_to_a_readable_measure(): void
    {
        $this->blade('<x-page-header title="Logs" description="What happened, and when." />')
            ->assertSee('max-w-[56ch] text-base text-pretty text-body', escape: false)
            ->assertSee('What happened, and when.', escape: false);
    }

    #[Test]
    public function it_stacks_until_it_is_given_something_to_do(): void
    {
        $this->blade('<x-page-header title="Templates" />')
            ->assertSee('flex flex-col gap-2', escape: false);

        $this->blade('<x-page-header title="Templates"><x-slot:actions>Add one</x-slot:actions></x-page-header>')
            ->assertSee('flex flex-wrap items-start justify-between', escape: false)
            ->assertSee('Add one', escape: false);
    }

    #[Test]
    public function no_shape_carries_two_layouts(): void
    {
        $stacked = $this->blade('<x-page-header title="Templates" />')->__toString();

        $this->assertStringNotContainsString('justify-between', $stacked);
    }

    #[Test]
    public function the_title_may_be_given_as_the_slot_instead(): void
    {
        $this->blade('<x-page-header>Kinds of record</x-page-header>')
            ->assertSee('Kinds of record', escape: false);
    }
}
