<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\RelationshipBehaviorEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipBehaviorEnumTest extends TestCase
{
    #[Test]
    public function it_names_the_two_behaviours_the_product_protects(): void
    {
        $this->assertEquals(
            ['genealogy_parentage', 'romantic'],
            array_column(RelationshipBehaviorEnum::cases(), 'value'),
        );
    }
}
