<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListPanelTest extends TestCase
{
    private const CALL = '<x-list-panel :items="$items">The rows<x-slot:empty>Nothing here yet</x-slot:empty></x-list-panel>';

    #[Test]
    public function it_shows_the_rows_when_it_holds_something(): void
    {
        $rendered = $this->blade(self::CALL, ['items' => new Collection(['Michael'])]);

        $rendered->assertSee('The rows', escape: false);
    }

    #[Test]
    public function it_shows_the_empty_state_when_it_holds_nothing(): void
    {
        $rendered = $this->blade(self::CALL, ['items' => new Collection]);

        $rendered->assertDontSee('The rows', escape: false);
    }

    #[Test]
    public function it_counts_a_plain_array_as_well_as_a_collection(): void
    {
        $this->blade(self::CALL, ['items' => []]);
        $this->blade(self::CALL, ['items' => ['Pam']])->assertSee('The rows', escape: false);
    }

    #[Test]
    public function it_offers_more_only_while_there_is_more(): void
    {
        $with = $this->blade(
            '<x-list-panel :items="$items" next-page-url="/logs?page=2">Rows<x-slot:empty>None</x-slot:empty></x-list-panel>',
            ['items' => new Collection(['Jim'])],
        );

        $with->assertSee('/logs?page=2', escape: false);

        $this->blade(self::CALL, ['items' => new Collection(['Jim'])]);
    }

    #[Test]
    public function it_marks_the_rows_for_replacement_only_when_asked(): void
    {
        $this->blade(
            '<x-list-panel :items="$items" id="logs" merge="append">Rows<x-slot:empty>None</x-slot:empty></x-list-panel>',
            ['items' => new Collection(['Jim'])],
        )->assertSee('x-merge="append"', escape: false);

        $this->blade(
            '<x-list-panel :items="$items" id="logs">Rows<x-slot:empty>None</x-slot:empty></x-list-panel>',
            ['items' => new Collection(['Jim'])],
        )->assertDontSee('x-merge', escape: false);
    }

    #[Test]
    public function it_draws_the_numbered_pages_when_given_a_paginator(): void
    {
        $paginator = new LengthAwarePaginator(items: ['Jim'], total: 41, perPage: 20, currentPage: 1, options: ['path' => '/logs']);

        $rendered = $this->blade(
            '<x-list-panel :items="$items" :paginator="$items" :size="20" :default="20">Rows<x-slot:empty>None</x-slot:empty></x-list-panel>',
            ['items' => $paginator],
        );

        $rendered->assertSee('aria-current="page"', escape: false);
    }

    #[Test]
    public function it_moves_focus_to_the_new_load_more_link_and_announces_what_arrived(): void
    {
        $rendered = $this->blade(
            '<x-list-panel :items="$items" id="logs" footer-id="logs-footer" next-page-url="/logs?page=2">Rows<x-slot:empty>None</x-slot:empty></x-list-panel>',
            ['items' => new Collection(['Jim', 'Pam'])],
        );

        $rendered->assertSee('x-autofocus', escape: false);
        $rendered->assertSee('aria-live="polite"', escape: false);
    }
}
