<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthFootnoteTest extends TestCase
{
    #[Test]
    public function it_offers_the_other_way_in(): void
    {
        $this->blade('<x-auth-footnote href="/login" label="Sign in instead">New to Monica?</x-auth-footnote>')
            ->assertSee('New to Monica?', escape: false)
            ->assertSee('href="/login"', escape: false)
            ->assertSee('Sign in instead', escape: false)
            ->assertSee('font-semibold text-ink', escape: false);
    }

    #[Test]
    public function it_may_carry_a_sentence_and_no_link(): void
    {
        $rendered = $this->blade('<x-auth-footnote>Passkeys need a secure connection.</x-auth-footnote>');

        $rendered->assertSee('Passkeys need a secure connection.', escape: false);
        $rendered->assertDontSee('font-semibold text-ink', escape: false);
    }

    #[Test]
    public function it_reads_as_an_aside_rather_than_as_the_card_above_it(): void
    {
        $this->blade('<x-auth-footnote>Anything</x-auth-footnote>')
            ->assertSee('rounded-lg text-center text-sm text-body', escape: false);
    }
}
