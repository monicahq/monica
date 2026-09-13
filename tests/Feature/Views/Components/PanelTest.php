<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PanelTest extends TestCase
{
    #[Test]
    public function it_shows_the_title_the_subtitle_and_what_it_holds(): void
    {
        $this->blade('<x-panel title="This vault" subtitle="Six of them" icon="vault">Rows</x-panel>')
            ->assertSee('This vault')
            ->assertSee('Six of them')
            ->assertSee('Rows');
    }

    #[Test]
    public function it_holds_what_it_is_given_without_a_title(): void
    {
        $this->blade('<x-panel>Anything</x-panel>')->assertSee('Anything');
    }
}
