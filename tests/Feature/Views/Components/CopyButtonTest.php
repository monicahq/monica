<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CopyButtonTest extends TestCase
{
    private const CALL = '<x-copy-button text="dunder-mifflin" :label="__(\'Copy to the clipboard\')" failure="No luck." />';

    #[Test]
    public function it_carries_the_value_rather_than_reading_it_back_off_the_screen(): void
    {
        $this->blade(self::CALL)
            ->assertSee("text: 'dunder-mifflin'", escape: false)
            ->assertSee('window.copyText(this.text)', escape: false)
            ->assertSee('Copy to the clipboard', escape: false);
    }

    #[Test]
    public function it_announces_both_what_worked_and_what_did_not(): void
    {
        $rendered = $this->blade(self::CALL);

        $rendered->assertSee('role="status"', escape: false);
        $rendered->assertSee('aria-live="polite"', escape: false);
        $rendered->assertSee("copied: 'Copied'", escape: false);
        $rendered->assertSee("failed: 'No luck.'", escape: false);
    }

    #[Test]
    public function it_lets_go_of_the_word_it_showed_so_the_button_may_be_pressed_again(): void
    {
        $this->blade(self::CALL)
            ->assertSee("this.state = 'idle'", escape: false)
            ->assertSee('2500', escape: false);
    }

    #[Test]
    public function it_does_not_submit_the_form_it_may_be_standing_in(): void
    {
        $this->blade(self::CALL)->assertSee('type="button"', escape: false);
    }
}
