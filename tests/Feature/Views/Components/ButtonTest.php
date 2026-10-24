<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ButtonTest extends TestCase
{
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
    public function it_goes_through_turbo_unless_the_caller_or_alpine_ajax_takes_the_link(): void
    {
        $this->blade('<x-button href="/vaults">Your vaults</x-button>')
            ->assertSee('data-turbo="true"', escape: false);

        $this->blade('<x-button href="/vaults" data-turbo="false">Your vaults</x-button>')
            ->assertSee('data-turbo="false"', escape: false)
            ->assertDontSee('data-turbo="true"', escape: false);

        $this->blade('<x-button href="/vaults" x-target="rows">Your vaults</x-button>')
            ->assertDontSee('data-turbo', escape: false);

        $this->blade('<x-button>Save</x-button>')
            ->assertDontSee('data-turbo', escape: false);
    }
}
