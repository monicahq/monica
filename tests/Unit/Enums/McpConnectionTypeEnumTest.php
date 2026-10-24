<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\McpConnectionTypeEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpConnectionTypeEnumTest extends TestCase
{
    #[Test]
    public function it_names_both_ways_an_assistant_connects(): void
    {
        foreach (McpConnectionTypeEnum::cases() as $type) {
            $this->assertNotEmpty($type->label());
        }

        $this->assertSame(['api_key', 'oauth_client'], array_column(McpConnectionTypeEnum::cases(), 'value'));
    }
}
