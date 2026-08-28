<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FontsTest extends TestCase
{
    #[Test]
    public function it_asks_for_no_font_a_visitor_has_to_download(): void
    {
        $config = (string) file_get_contents(base_path('vite.config.js'));

        $this->assertStringNotContainsString('bunny(', $config);
        $this->assertStringNotContainsString('fonts:', $config);
    }

    #[Test]
    public function it_sets_the_interface_in_what_the_machine_already_has(): void
    {
        $theme = (string) file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('--font-sans: ui-sans-serif, system-ui', $theme);
        $this->assertStringContainsString('--font-mono: ui-monospace', $theme);
        $this->assertStringNotContainsString('@font-face', $theme);
    }
}
