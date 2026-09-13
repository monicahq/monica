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
}
