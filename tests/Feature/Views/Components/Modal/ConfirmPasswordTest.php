<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components\Modal;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConfirmPasswordTest extends TestCase
{
    #[Test]
    public function it_is_a_dialog_that_traps_the_focus(): void
    {
        $this->blade('<x-modal.confirm-password :offers-password="true" />')
            ->assertSee('role="dialog"', escape: false)
            ->assertSee('aria-modal="true"', escape: false)
            ->assertSee('x-trap.noscroll.inert="open"', escape: false)
            ->assertSee('aria-live="polite"', escape: false);
    }

    #[Test]
    public function it_asks_for_the_password_when_the_account_has_one(): void
    {
        $this->blade('<x-modal.confirm-password :offers-password="true" />')
            ->assertSee('Your password', escape: false)
            ->assertDontSee('Use a passkey', escape: false);
    }

    #[Test]
    public function it_asks_for_the_passkey_alone_when_there_is_no_password(): void
    {
        $this->blade('<x-modal.confirm-password :offers-passkey="true" />')
            ->assertSee('Use a passkey', escape: false)
            ->assertDontSee('Your password', escape: false);
    }

    #[Test]
    public function it_says_so_when_the_account_holds_neither_credential(): void
    {
        $this->blade('<x-modal.confirm-password />')
            ->assertSee('This account holds neither a password nor a passkey', escape: false)
            ->assertDontSee('Your password', escape: false)
            ->assertDontSee('Use a passkey', escape: false);
    }

    #[Test]
    public function it_submits_straight_through_when_the_password_is_already_confirmed(): void
    {
        $this->blade('<x-modal.confirm-password :confirmed="true" :seconds-left="900" :offers-password="true" />')
            ->assertSee('confirmed\\u0022:true', escape: false)
            ->assertSee('secondsLeft\\u0022:900', escape: false);
    }

    #[Test]
    public function its_alpine_expression_is_balanced(): void
    {
        $rendered = $this->blade('<x-modal.confirm-password :offers-password="true" :offers-passkey="true" />')->__toString();

        preg_match_all('/x-data="([^"]*)"/', $rendered, $matches);

        $this->assertNotEmpty($matches[1]);

        foreach ($matches[1] as $expression) {
            $this->assertSame(
                substr_count($expression, '('),
                substr_count($expression, ')'),
                "Unbalanced parentheses in x-data: {$expression}",
            );
        }
    }
}
