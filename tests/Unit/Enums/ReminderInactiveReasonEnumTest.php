<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ReminderInactiveReasonEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderInactiveReasonEnumTest extends TestCase
{
    #[Test]
    public function it_lists_its_values_with_no_pause_among_them(): void
    {
        $this->assertEquals([
            'value_cleared',
            'record_archived',
            'record_deleted',
            'field_removed',
        ], ReminderInactiveReasonEnum::values());
    }
}
