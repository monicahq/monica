<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InputTest extends TestCase
{
    #[Test]
    public function it_wires_the_label_the_hint_and_the_error_to_the_control(): void
    {
        $rendered = $this->blade(
            '<x-input id="email" label="Email address" help="This is what you sign in with." :error="$error" />',
            ['error' => ['That address is already taken.']],
        );

        $rendered->assertSee('erica-field', escape: false);
        $rendered->assertSee('<label class="erica-field__label" for="email">', escape: false);
        $rendered->assertSee('aria-describedby="email-help email-error"', escape: false);
        $rendered->assertSee('aria-invalid="true"', escape: false);
        $rendered->assertSee('id="email-help"', escape: false);
        $rendered->assertSee('That address is already taken.', escape: false);
    }

    #[Test]
    public function it_carries_no_described_by_when_there_is_nothing_to_describe_it(): void
    {
        $this->blade('<x-input id="first_name" label="First name" />')
            ->assertDontSee('aria-describedby', escape: false)
            ->assertDontSee('aria-invalid', escape: false);
    }

    #[Test]
    public function it_is_drawn_small_only_when_it_is_asked_to_be(): void
    {
        $this->blade('<x-input id="q" />')
            ->assertSee('class="erica-control"', escape: false);

        $this->blade('<x-input id="q" size="small" />')
            ->assertSee('erica-control erica-control--sm', escape: false);
    }

    #[Test]
    public function it_says_which_fields_are_optional(): void
    {
        $this->blade('<x-input id="nickname" label="Nickname" :optional="true" />')
            ->assertSee('erica-field__note', escape: false)
            ->assertSee('optional', escape: false);
    }

    #[Test]
    public function a_textarea_reads_the_same_props_and_is_the_same_control(): void
    {
        $rendered = $this->blade('<x-textarea id="notes" label="Notes" value="Sold paper." />');

        $rendered->assertSee('erica-control erica-control--textarea', escape: false);
        $rendered->assertSee('<label class="erica-field__label" for="notes">', escape: false);
        $rendered->assertSee('Sold paper.', escape: false);
    }

    #[Test]
    public function a_file_field_drops_the_well_it_has_no_text_in(): void
    {
        $this->blade('<x-file-input id="photo" accept="image/png" />')
            ->assertSee('erica-control erica-control--file', escape: false)
            ->assertSee('type="file"', escape: false)
            ->assertSee('accept="image/png"', escape: false);
    }

    #[Test]
    public function an_error_carries_a_glyph_as_well_as_the_colour(): void
    {
        $rendered = $this->blade('<x-error :messages="$messages" />', ['messages' => ['First things first.', 'Then this.']]);

        $rendered->assertSee('erica-field__error', escape: false);
        $rendered->assertSee('First things first.', escape: false);
        $rendered->assertSee('Then this.', escape: false);
        $this->assertSame(2, substr_count($rendered->__toString(), '<svg'));
    }

    #[Test]
    public function an_error_with_nothing_to_say_renders_nothing(): void
    {
        $this->assertSame('', trim($this->blade('<x-error :messages="null" />')->__toString()));
    }
}
