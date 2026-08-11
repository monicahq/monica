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
        $this->blade('<x-button>Save</x-button>')->assertSee('erica-btn erica-btn--primary', escape: false);
        $this->blade('<x-button.secondary>Cancel</x-button.secondary>')->assertSee('erica-btn erica-btn--secondary', escape: false);
        $this->blade('<x-button.ghost>Remove</x-button.ghost>')->assertSee('erica-btn erica-btn--ghost', escape: false);
        $this->blade('<x-button.danger>Delete it for good</x-button.danger>')->assertSee('erica-btn erica-btn--danger', escape: false);
    }

    #[Test]
    public function it_is_the_default_height_unless_it_is_asked_to_be_small(): void
    {
        $this->blade('<x-button>Save</x-button>')->assertSee('erica-btn--md', escape: false);
        $this->blade('<x-button size="small">Save</x-button>')->assertSee('erica-btn--sm', escape: false);
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
            ->assertSee('erica-btn erica-btn--primary erica-btn--md ml-auto', escape: false);
    }
}
