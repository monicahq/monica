<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PanelTest extends TestCase
{
    #[Test]
    public function it_draws_no_header_when_it_has_no_title(): void
    {
        $rendered = $this->blade('<x-panel>Anything</x-panel>');

        $rendered->assertSee('rounded-xl border shadow-panel', escape: false);
        $rendered->assertSee('border-hairline bg-canvas', escape: false);
        $rendered->assertSee('Anything', escape: false);
        $rendered->assertDontSee('<header', escape: false);
    }

    #[Test]
    public function it_names_itself_and_marks_itself_when_it_is_given_a_title(): void
    {
        $rendered = $this->blade('<x-panel title="This vault" subtitle="Six of them" icon="vault">Rows</x-panel>');

        $rendered->assertSee('<h2 class="text-card-title text-ink">This vault</h2>', escape: false);
        $rendered->assertSee('Six of them', escape: false);
        $rendered->assertSee('rounded-md bg-primary text-on-primary', escape: false);
    }

    #[Test]
    public function it_sets_its_header_on_a_band_and_its_footer_on_a_deeper_one(): void
    {
        $rendered = $this->blade('<x-panel title="This vault">Rows<x-slot:footer>Save it</x-slot:footer></x-panel>');

        $rendered->assertSee('border-b px-5 py-4 border-hairline-soft bg-sunken', escape: false);
        $rendered->assertSee('border-t border-hairline-soft bg-sand px-5 py-3.5', escape: false);
    }

    #[Test]
    public function the_panel_that_ends_something_says_so_before_it_is_read(): void
    {
        $rendered = $this->blade('<x-panel title="Delete this vault" icon="vault" tone="danger">Gone</x-panel>');

        $rendered->assertSee('border-error-edge bg-canvas', escape: false);
        $rendered->assertSee('border-error-edge bg-error-tint', escape: false);
        $rendered->assertSee('rounded-md bg-error text-white', escape: false);
        $rendered->assertSee('<h2 class="text-card-title text-error">Delete this vault</h2>', escape: false);
    }

    #[Test]
    public function it_takes_the_padding_off_its_body_for_a_list_of_rows(): void
    {
        $this->blade('<x-panel>Prose</x-panel>')
            ->assertSee('<div class="p-5">', escape: false);

        $this->blade('<x-panel :flush="true">Rows</x-panel>')
            ->assertDontSee('<div class="p-5">', escape: false);
    }
}
