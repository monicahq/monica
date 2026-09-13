<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Contracts\Translatable;
use App\Enums\RelationshipStatusEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipStatusEnumTest extends TestCase
{
    #[Test]
    public function it_holds_the_two_states_a_relationship_can_be_in(): void
    {
        $this->assertEquals(['current', 'ended'], RelationshipStatusEnum::values());
    }

    #[Test]
    public function it_reads_each_state_back_to_the_user(): void
    {
        $this->assertEquals('Current', RelationshipStatusEnum::Current->label());
        $this->assertEquals('Ended', RelationshipStatusEnum::Ended->label());
    }

    #[Test]
    public function it_offers_its_wording_to_the_translator(): void
    {
        $this->assertInstanceOf(Translatable::class, RelationshipStatusEnum::Current);

        foreach (RelationshipStatusEnum::cases() as $case) {
            $this->assertEquals([$case->label()], $case->translationKeys());
        }
    }
}
