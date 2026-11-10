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
            ->assertSee('Passkeys', escape: false)
            ->assertSee('Kept on your device.', escape: false)
            ->assertSee('The panel', escape: false);
    }

    #[Test]
    public function it_may_carry_help_beside_its_heading_without_renaming_the_heading(): void
    {
        $rendered = (string) $this->blade('<x-page-section title="Reception">The desk<x-slot:help><button type="button">Help about reception</button></x-slot:help></x-page-section>');

        preg_match('/<h2[^>]*>(.*?)<\/h2>/s', $rendered, $heading);

        $this->assertStringContainsString('Help about reception', $rendered);
        $this->assertSame('Reception', trim($heading[1]));
    }

    #[Test]
    public function it_may_carry_a_count_beside_its_heading(): void
    {
        $this->blade('<x-page-section title="Your types"><x-slot:aside>4 types</x-slot:aside>Rows</x-page-section>')
            ->assertSee('4 types', escape: false);
    }
}
