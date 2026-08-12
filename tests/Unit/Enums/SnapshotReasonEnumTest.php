<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\SnapshotReasonEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SnapshotReasonEnumTest extends TestCase
{
    #[Test]
    public function it_says_which_reasons_the_product_acted_on_somebody_s_behalf_for(): void
    {
        $this->assertTrue(SnapshotReasonEnum::TemplateChange->isAutomatic());
        $this->assertTrue(SnapshotReasonEnum::Merge->isAutomatic());
        $this->assertTrue(SnapshotReasonEnum::Import->isAutomatic());
        $this->assertTrue(SnapshotReasonEnum::Restore->isAutomatic());
    }

    #[Test]
    public function it_says_which_reasons_somebody_asked_for(): void
    {
        $this->assertFalse(SnapshotReasonEnum::Manual->isAutomatic());
        $this->assertFalse(SnapshotReasonEnum::Api->isAutomatic());
    }
}
