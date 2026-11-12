<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IconButtonTest extends TestCase
{
    #[Test]
    public function it_draws_a_button_when_it_is_given_nowhere_to_go(): void
    {
        $rendered = $this->blade('<x-icon-button icon="check" :label="__(\'I am done with this one\')" />');

        $rendered->assertSee('<button', escape: false);
        $rendered->assertSee('type="button"', escape: false);
        $rendered->assertDontSee('<a href', escape: false);
    }

    #[Test]
    public function it_draws_a_link_when_it_is_given_somewhere_to_go(): void
    {
        $rendered = $this->blade('<x-icon-button href="/somewhere" icon="reminder" :label="__(\'Open this reminder\')" />');

        $rendered->assertSee('<a href="/somewhere"', escape: false);
        $rendered->assertDontSee('<button', escape: false);
    }

    #[Test]
    public function it_says_its_name_to_a_screen_reader_and_shows_it_to_everybody_else(): void
    {
        $rendered = $this->blade('<x-icon-button href="/somewhere" icon="reminder" label="Open this reminder" />');

        $rendered->assertSee('aria-label="Open this reminder"', escape: false);
        $rendered->assertSee('Open this reminder');
    }

    #[Test]
    public function it_submits_a_form_when_it_is_asked_to(): void
    {
        $this->blade('<x-icon-button type="submit" icon="mail" label="Send it to me now" />')
            ->assertSee('type="submit"', escape: false);
    }
}
