<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Models\EntityType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntityIconTest extends TestCase
{
    #[Test]
    #[DataProvider('provideTheIconsATypeMayWear')]
    public function it_draws_a_glyph_of_its_own_for_every_icon_a_type_may_wear(string $name): void
    {
        $this->assertNotSame(
            (string) $this->blade('<x-entity-icon name="unknown" />'),
            (string) $this->blade('<x-entity-icon :name="$name" />', ['name' => $name]),
        );
    }

    /** @return array<int, array<int, string>> */
    public static function provideTheIconsATypeMayWear(): array
    {
        return array_map(
            fn (string $icon): array => [$icon],
            array_values(array_diff(EntityType::ICONS, [EntityType::DEFAULT_ICON])),
        );
    }
}
