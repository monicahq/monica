<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\NotificationDeliveryModeEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationDeliveryModeEnumTest extends TestCase
{
    #[Test]
    public function it_lists_its_values(): void
    {
        $this->assertEquals(['immediate', 'digest'], NotificationDeliveryModeEnum::values());
    }
}
