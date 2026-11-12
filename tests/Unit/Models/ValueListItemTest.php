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
}
