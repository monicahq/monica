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
    public function it_offers_a_link_by_email_when_the_account_holds_neither_credential(): void
    {
        $this->blade('<x-modal.confirm-password />')
            ->assertSee('action="'.route('auth.passwordConfirmationLink.create').'"', escape: false);
    }

    #[Test]
    public function it_says_why_it_asks_in_the_words_it_is_given(): void
    {
        $this->blade('<x-modal.confirm-password :offers-password="true" description="Michael wants to change the paper supplier." />')
            ->assertSee('Michael wants to change the paper supplier.', escape: false);
    }

    #[Test]
    public function it_asks_the_server_whether_the_confirmation_still_holds(): void
    {
        $rendered = $this->blade('<x-modal.confirm-password :offers-password="true" />')->__toString();

        preg_match("/confirmPassword\\(JSON\\.parse\\('([^']*)'\\)\\)/", $rendered, $matches);

        $config = json_decode(json_decode("\"{$matches[1]}\""), true);

        $this->assertSame(route('auth.passwordConfirmationStatus.show'), $config['statusUrl']);
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
