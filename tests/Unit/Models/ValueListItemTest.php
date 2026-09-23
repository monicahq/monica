<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\PhoneLabelEnum;
use App\Models\ValueListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ValueListItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_by_the_label_the_user_gave_it(): void
    {
        $item = ValueListItem::factory()->create(['label' => 'Beet farm']);

        $this->assertEquals('Beet farm', $item->label);
    }

    #[Test]
    public function it_falls_back_to_its_translation_key_while_it_has_no_label(): void
    {
        $item = ValueListItem::factory()->supplied(PhoneLabelEnum::Mobile)->create();

        $this->assertNull($item->getRawOriginal('label'));
        $this->assertEquals('Mobile', $item->label);
    }

    #[Test]
    public function it_reads_a_supplied_label_in_the_language_of_whoever_is_looking(): void
    {
        $item = ValueListItem::factory()->supplied(PhoneLabelEnum::Mobile)->create();

        $this->app->setLocale('fr_FR');

        $this->assertEquals('Mobile', $item->label);
    }

    #[Test]
    public function it_is_offered_and_used_by_nothing_to_begin_with(): void
    {
        $item = ValueListItem::factory()->create();

        $this->assertTrue($item->is_active);
        $this->assertSame(0, $item->usage_count);
        $this->assertNull($item->colour);
        $this->assertNull($item->icon);
    }

    #[Test]
    public function it_is_deleted_softly(): void
    {
        $item = ValueListItem::factory()->create();

        $item->delete();

        $this->assertSoftDeleted('value_list_items', ['id' => $item->id]);
        $this->assertEquals(0, ValueListItem::query()->count());
    }
}
