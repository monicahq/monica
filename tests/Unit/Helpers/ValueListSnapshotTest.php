<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\ValueListSnapshot;
use App\Models\Field;
use App\Models\SectionVersion;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ValueListSnapshotTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_copies_the_lists_the_fields_read_their_choices_from(): void
    {
        $version = SectionVersion::factory()->create();
        $list = ValueList::query()->create([
            'account_id' => $version->account_id,
            'key' => 'departments',
            'name' => 'Departments',
            'name_translation_key' => null,
            'description' => null,
            'is_system' => false,
            'allows_user_values' => true,
            'position' => 1,
        ]);
        $item = ValueListItem::query()->create([
            'account_id' => $version->account_id,
            'value_list_id' => $list->id,
            'key' => 'sales',
            'label' => 'Sales',
            'label_translation_key' => null,
            'position' => 1,
            'is_system' => false,
            'is_active' => true,
            'usage_count' => 0,
        ]);

        Field::factory()->create([
            'account_id' => $version->account_id,
            'section_version_id' => $version->id,
            'value_list_id' => $list->id,
        ]);

        $snapshot = ValueListSnapshot::of($version);

        $this->assertCount(1, $snapshot);
        $this->assertEquals('departments', $snapshot[$list->id]['key']);
        $this->assertEquals('Departments', $snapshot[$list->id]['name']);
        $this->assertNull($snapshot[$list->id]['name_translation_key']);
        $this->assertEquals(
            [[
                'id' => $item->id,
                'key' => 'sales',
                'label' => 'Sales',
                'label_translation_key' => null,
                'position' => 1,
                'is_active' => true,
            ]],
            $snapshot[$list->id]['items'],
        );
    }

    #[Test]
    public function it_copies_nothing_when_no_field_offers_a_choice(): void
    {
        $version = SectionVersion::factory()->create();

        Field::factory()->create([
            'account_id' => $version->account_id,
            'section_version_id' => $version->id,
            'value_list_id' => null,
        ]);

        $this->assertEquals([], ValueListSnapshot::of($version));
    }
}
