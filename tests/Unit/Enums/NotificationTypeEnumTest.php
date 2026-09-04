<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\NotificationTypeEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationTypeEnumTest extends TestCase
{
    #[Test]
    public function it_lists_its_values(): void
    {
        $this->assertEquals(['reminder_due', 'digest'], NotificationTypeEnum::values());
    }
}
