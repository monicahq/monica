<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\McpCallResultEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpCallResultEnumTest extends TestCase
{
    #[Test]
    public function it_names_every_outcome_of_a_tool_call(): void
    {
        foreach (McpCallResultEnum::cases() as $result) {
            $this->assertNotEmpty($result->label());
        }

        $this->assertSame(
            ['succeeded', 'refused', 'invalid_parameters', 'error', 'interrupted'],
            array_column(McpCallResultEnum::cases(), 'value'),
        );
    }

    #[Test]
    public function it_gives_a_tone_the_badge_knows_to_every_outcome(): void
    {
        $this->assertSame('success', McpCallResultEnum::Succeeded->tone());
        $this->assertSame('warning', McpCallResultEnum::Refused->tone());
        $this->assertSame('warning', McpCallResultEnum::InvalidParameters->tone());
        $this->assertSame('danger', McpCallResultEnum::Error->tone());
        $this->assertSame('neutral', McpCallResultEnum::Interrupted->tone());
    }
}
