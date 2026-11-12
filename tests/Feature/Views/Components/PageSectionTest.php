<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageSectionTest extends TestCase
{
    #[Test]
    public function it_may_carry_help_beside_its_heading_without_renaming_the_heading(): void
    {
        $rendered = (string) $this->blade('<x-page-section title="Reception">The desk<x-slot:help><button type="button">Help about reception</button></x-slot:help></x-page-section>');

        preg_match('/<h2[^>]*>(.*?)<\/h2>/s', $rendered, $heading);

        $this->assertStringContainsString('Help about reception', $rendered);
        $this->assertSame('Reception', trim($heading[1]));
    }
}
