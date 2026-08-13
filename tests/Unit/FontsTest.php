<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FontsTest extends TestCase
{
    #[Test]
    public function every_bundled_family_carries_the_letters_names_are_written_with(): void
    {
        $config = (string) file_get_contents(base_path('vite.config.js'));

        preg_match_all('/bunny\(\s*\'([^\']+)\'.*?\)/s', $config, $matches, PREG_SET_ORDER);

        $this->assertNotEmpty($matches);

        foreach ($matches as $family) {
            $this->assertStringContainsString('latin-ext', $family[0], "{$family[1]} drops Latin Extended-A.");
            $this->assertStringContainsString('vietnamese', $family[0], "{$family[1]} drops Vietnamese.");
        }
    }
}
