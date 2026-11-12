<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\FieldTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldTypeEnumTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_never_names_a_column_for_a_type_that_stores_nothing(): void
    {
        foreach (FieldTypeEnum::cases() as $type) {
            if ($type->storesValue()) {
                continue;
            }

            $this->assertNull($type->valueColumn());
        }
    }

    #[Test]
    public function it_never_offers_a_control_for_a_type_that_stores_nothing(): void
    {
        foreach (FieldTypeEnum::cases() as $type) {
            if ($type->storesValue()) {
                continue;
            }

            $this->assertFalse($type->hasAnInput());
        }
    }

    #[Test]
    public function every_column_a_type_writes_to_exists_on_the_field_values(): void
    {
        foreach (FieldTypeEnum::cases() as $type) {
            $column = $type->valueColumn();

            if ($column === null) {
                continue;
            }

            $this->assertTrue(
                Schema::hasColumn('field_values', $column),
                "{$type->name} writes to field_values.{$column}, which does not exist.",
            );
        }
    }
}
