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

        $rendered->assertSee('for="email"', escape: false);
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
    public function it_says_which_fields_are_optional(): void
    {
        $this->blade('<x-input id="nickname" label="Nickname" :optional="true" />')
            ->assertSee('optional');
    }

    #[Test]
    public function an_error_says_every_message_it_is_given(): void
    {
        $this->blade('<x-error :messages="$messages" />', ['messages' => ['First things first.', 'Then this.']])
            ->assertSee('First things first.')
            ->assertSee('Then this.');
    }

    #[Test]
    public function an_error_with_nothing_to_say_renders_nothing(): void
    {
        $this->assertSame('', trim($this->blade('<x-error :messages="null" />')->__toString()));
    }
}
