<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SelectTest extends TestCase
{
    #[Test]
    public function it_wires_the_label_the_hint_and_the_error_to_the_control(): void
    {
        $rendered = $this->blade(
            '<x-select id="branch" :label="__(\'Branch\')" :help="__(\'Where they work.\')" :error="__(\'Pick one.\')">'
            .'<option value="scranton">Scranton</option>'
            .'</x-select>',
        );

        $rendered->assertSee('for="branch"', escape: false);
        $rendered->assertSee('aria-describedby="branch-help branch-error"', escape: false);
        $rendered->assertSee('aria-invalid="true"', escape: false);
        $rendered->assertSee('id="branch-help"', escape: false);
        $rendered->assertSee('Where they work.', escape: false);
        $rendered->assertSee('Pick one.', escape: false);
        $rendered->assertSee('<option value="scranton">Scranton</option>', escape: false);
    }

    #[Test]
    public function its_name_follows_its_id_unless_it_is_given_one(): void
    {
        $this->blade('<x-select id="branch"><option value="x">X</option></x-select>')
            ->assertSee('name="branch"', escape: false);

        $this->blade('<x-select id="branch-2" name="branch"><option value="x">X</option></x-select>')
            ->assertSee('name="branch"', escape: false);
    }

    #[Test]
    public function its_hint_may_be_a_slot_when_it_changes_as_the_field_changes(): void
    {
        $rendered = $this->blade(
            '<x-select id="branch"><option value="x">X</option>'
            .'<x-slot:help><span x-text="written"></span></x-slot:help>'
            .'</x-select>',
        );

        $rendered->assertSee('<span x-text="written"></span>', escape: false);
        $rendered->assertSee('id="branch-help"', escape: false);
    }
}
