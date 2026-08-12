<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultSearchTest extends TestCase
{
    #[Test]
    public function it_is_the_door_onto_the_launcher(): void
    {
        $this->blade('<x-vault.search />')
            ->assertSee("\$dispatch('launcher-open')", escape: false)
            ->assertSee('Search people, notes, anything…')
            ->assertSee('⌘K');
    }

    #[Test]
    public function it_is_a_well_on_sand_rather_than_the_white_of_the_bar(): void
    {
        $this->blade('<x-vault.search />')
            ->assertSee('bg-sand', escape: false)
            ->assertSee('shadow-press-inset', escape: false)
            ->assertDontSee('bg-canvas', escape: false);
    }

    #[Test]
    public function it_leaves_the_bar_to_the_vault_on_a_narrow_window(): void
    {
        $this->blade('<x-vault.search />')
            ->assertSee('hidden', escape: false)
            ->assertSee('min-[900px]:flex', escape: false);
    }
}
