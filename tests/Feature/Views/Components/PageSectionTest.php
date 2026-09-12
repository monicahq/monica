<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageSectionTest extends TestCase
{
    #[Test]
    public function it_names_what_it_holds_and_holds_it(): void
    {
        $this->blade('<x-page-section title="Passkeys" description="Kept on your device.">The panel</x-page-section>')
            ->assertSee('shrink-0 text-section-title text-ink', escape: false)
            ->assertSee('Passkeys', escape: false)
            ->assertSee('text-sm text-pretty text-muted', escape: false)
            ->assertSee('Kept on your device.', escape: false)
            ->assertSee('The panel', escape: false);
    }

    #[Test]
    public function a_section_with_no_title_shows_no_heading_at_all(): void
    {
        $rendered = $this->blade('<x-page-section :title="null">Just the panel</x-page-section>');

        $rendered->assertSee('Just the panel', escape: false);
        $rendered->assertDontSee('text-section-title', escape: false);
    }

    #[Test]
    public function it_may_carry_a_count_at_the_far_end_of_its_heading(): void
    {
        $this->blade('<x-page-section title="Your types"><x-slot:aside>4 types</x-slot:aside>Rows</x-page-section>')
            ->assertSee('ml-auto text-xs text-pretty text-muted', escape: false)
            ->assertSee('4 types', escape: false);
    }

    #[Test]
    public function the_description_is_left_out_when_there_is_none(): void
    {
        $this->blade('<x-page-section title="Password">Rows</x-page-section>')
            ->assertDontSee('text-sm text-pretty text-muted', escape: false);
    }
}
