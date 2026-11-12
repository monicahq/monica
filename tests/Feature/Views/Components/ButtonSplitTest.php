<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ButtonSplitTest extends TestCase
{
    #[Test]
    public function the_half_that_carries_the_label_submits_the_form_it_sits_in(): void
    {
        $rendered = $this->blade(
            '<x-button.split label="Create a new version"><x-slot:menu><a href="/share">Share on marketplace</a></x-slot:menu></x-button.split>',
        );

        $rendered->assertSee('type="submit"', escape: false);
        $rendered->assertSee('Create a new version', escape: false);
    }

    #[Test]
    public function the_chevron_opens_a_menu_rather_than_submitting_anything(): void
    {
        $rendered = $this->blade(
            '<x-button.split label="Create a new version"><x-slot:menu><a href="/share">Share on marketplace</a></x-slot:menu></x-button.split>',
        );

        $rendered->assertSee('type="button"', escape: false);
        $rendered->assertSee('aria-haspopup="menu"', escape: false);
        $rendered->assertSee('role="menu"', escape: false);
        $rendered->assertSee('More actions', escape: false);
        $rendered->assertSee('Share on marketplace', escape: false);
    }

    #[Test]
    public function a_menu_with_nothing_in_it_draws_no_chevron(): void
    {
        $rendered = $this->blade(
            '<x-button.split label="Create a new version"><x-slot:menu></x-slot:menu></x-button.split>',
        );

        $rendered->assertSee('Create a new version', escape: false);
        $rendered->assertDontSee('aria-haspopup="menu"', escape: false);
        $rendered->assertDontSee('role="menu"', escape: false);
        $rendered->assertDontSee('More actions', escape: false);
    }
}
