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
        $this->assertNotNull($item->label);
    }

    #[Test]
    public function it_reads_the_country_it_stands_for(): void
    {
        $supplied = ValueListItem::factory()->make(['key' => 'fr', 'country_code' => null]);
        $picked = ValueListItem::factory()->make(['key' => null, 'country_code' => 'jp']);
        $typed = ValueListItem::factory()->make(['key' => null, 'country_code' => null, 'label' => 'Kurdistan']);

        $this->assertEquals('FR', $supplied->countryCode());
        $this->assertEquals('JP', $picked->countryCode());
        $this->assertNull($typed->countryCode());
    }

    #[Test]
    public function it_reads_a_picked_country_in_the_language_of_the_reader(): void
    {
        $picked = ValueListItem::factory()->make(['label' => null, 'label_translation_key' => null, 'country_code' => 'LT']);
        $written = ValueListItem::factory()->make(['label' => 'Scranton Republic', 'country_code' => 'LT']);

        app()->setLocale('fr_FR');

        $this->assertEquals('Lituanie', $picked->label);
        $this->assertEquals('Scranton Republic', $written->label);
    }
}
