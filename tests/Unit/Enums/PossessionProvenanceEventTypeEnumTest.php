<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\PossessionProvenanceEventTypeEnum;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PossessionProvenanceEventTypeEnumTest extends TestCase
{
    #[Test]
    public function the_types_fall_in_three_groups_in_the_order_of_the_form(): void
    {
        $groups = [];

        foreach (PossessionProvenanceEventTypeEnum::cases() as $type) {
            $groups[$type->group()][] = $type;
        }

        $this->assertEquals([
            'ownership' => [PossessionProvenanceEventTypeEnum::Origin, PossessionProvenanceEventTypeEnum::Acquisition, PossessionProvenanceEventTypeEnum::Inheritance, PossessionProvenanceEventTypeEnum::Gift, PossessionProvenanceEventTypeEnum::Sale],
            'custody' => [PossessionProvenanceEventTypeEnum::Custody, PossessionProvenanceEventTypeEnum::Loss, PossessionProvenanceEventTypeEnum::Recovery],
            'authenticity' => [PossessionProvenanceEventTypeEnum::Authentication, PossessionProvenanceEventTypeEnum::Exhibition, PossessionProvenanceEventTypeEnum::Publication, PossessionProvenanceEventTypeEnum::Other],
        ], $groups);
    }
}
