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

        $rendered->assertSee('erica-panel', escape: false);
        $rendered->assertSee('Anything', escape: false);
        $rendered->assertDontSee('erica-panel__head', escape: false);
    }

    #[Test]
    public function it_names_itself_and_marks_itself_when_it_is_given_a_title(): void
    {
        $rendered = $this->blade('<x-panel title="This vault" subtitle="Six of them" icon="vault" icon-color="var(--sky)" icon-background="var(--sky-tint)">Rows</x-panel>');

        $rendered->assertSee('<h2 class="erica-panel__title">This vault</h2>', escape: false);
        $rendered->assertSee('Six of them', escape: false);
        $rendered->assertSee('background: var(--sky-tint); color: var(--sky)', escape: false);
    }

    #[Test]
    public function it_takes_the_padding_off_its_body_for_a_list_of_rows(): void
    {
        $this->blade('<x-panel>Prose</x-panel>')
            ->assertDontSee('erica-panel__body--flush', escape: false);

        $this->blade('<x-panel :flush="true">Rows</x-panel>')
            ->assertSee('erica-panel__body--flush', escape: false);
    }
}
