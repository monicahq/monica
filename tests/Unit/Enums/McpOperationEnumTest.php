<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\McpOperationEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpOperationEnumTest extends TestCase
{
    #[Test]
    public function it_reads_the_operation_from_the_method_of_the_mirrored_route(): void
    {
        $this->assertSame(McpOperationEnum::Read, McpOperationEnum::fromHttpMethod('GET|HEAD'));
        $this->assertSame(McpOperationEnum::Create, McpOperationEnum::fromHttpMethod('POST'));
        $this->assertSame(McpOperationEnum::Update, McpOperationEnum::fromHttpMethod('PUT'));
        $this->assertSame(McpOperationEnum::Update, McpOperationEnum::fromHttpMethod('PATCH'));
        $this->assertSame(McpOperationEnum::Delete, McpOperationEnum::fromHttpMethod('DELETE'));
    }

    #[Test]
    public function it_guesses_nothing_for_a_method_it_does_not_know(): void
    {
        $this->assertNull(McpOperationEnum::fromHttpMethod('OPTIONS'));
        $this->assertNull(McpOperationEnum::fromHttpMethod(''));
    }
}
