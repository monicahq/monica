<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ButtonTest extends TestCase
{
    #[Test]
    public function each_variant_draws_itself(): void
    {
        $this->blade('<x-button>Save</x-button>')->assertSee('bg-action-primary', escape: false);
        $this->blade('<x-button.secondary>Cancel</x-button.secondary>')->assertSee('bg-canvas', escape: false);
        $this->blade('<x-button.bar>Your vaults</x-button.bar>')->assertSee('bg-sand text-ink hover:border-ink', escape: false);
        $this->blade('<x-button.ghost>Remove</x-button.ghost>')->assertSee('hover:bg-sand hover:text-ink', escape: false);
        $this->blade('<x-button.danger>Delete it for good</x-button.danger>')->assertSee('bg-error', escape: false);
    }

    #[Test]
    public function the_bar_variant_is_not_the_white_of_the_bar_it_sits_on(): void
    {
        $this->blade('<x-button.bar>Your vaults</x-button.bar>')
            ->assertSee('bg-sand', escape: false)
            ->assertDontSee('bg-canvas', escape: false);
    }

    #[Test]
    public function it_is_the_default_height_unless_it_is_asked_to_be_small(): void
    {
        $this->blade('<x-button>Save</x-button>')->assertSee('h-10 px-4.5', escape: false);
        $this->blade('<x-button size="small">Save</x-button>')->assertSee('h-8.5 px-3.25', escape: false);
    }

    #[Test]
    public function it_becomes_an_anchor_when_it_is_given_somewhere_to_go(): void
    {
        $this->blade('<x-button href="/vaults">Your vaults</x-button>')
            ->assertSee('<a href="/vaults"', escape: false)
            ->assertDontSee('<button', escape: false);

        $this->blade('<x-button>Save</x-button>')
            ->assertSee('<button type="submit"', escape: false);
    }

    #[Test]
    public function a_class_written_on_it_still_wins(): void
    {
        $this->blade('<x-button class="ml-auto">Save</x-button>')
            ->assertSee('h-10 px-4.5 text-base ml-auto', escape: false);
    }
}
