<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Templates;

use App\Enums\FieldTypeEnum;
use App\Enums\ValueListEnum;
use App\Models\EntityType;
use App\Models\ValueList;
use App\ViewModels\Settings\Account\Templates\FieldCatalogueViewModel;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldCatalogueViewModelTest extends TestCase
{
    #[Test]
    public function it_marks_as_available_exactly_what_the_enum_ships(): void
    {
        $available = [];

        foreach ($this->catalogue()->families() as $family) {
            foreach ($family['types'] as $type) {
                if ($type['isAvailable']) {
                    $available[] = $type['key'];
                }
            }
        }

        sort($available);
        $shipped = array_map(fn (FieldTypeEnum $type): string => $type->value, FieldTypeEnum::cases());
        sort($shipped);

        $this->assertEquals($shipped, $available);
    }

    #[Test]
    public function it_names_every_type_the_enum_ships(): void
    {
        foreach (FieldTypeEnum::cases() as $type) {
            $this->assertNotEquals($type->value, FieldCatalogueViewModel::labelFor($type));
        }
    }

    #[Test]
    public function it_offers_no_repeater_to_a_field_added_inside_one(): void
    {
        $inside = array_column(new FieldCatalogueViewModel(insideARepeater: true)->types(), 'key');

        $this->assertNotContains('repeater', $inside);
        $this->assertContains('relation', $inside);
        $this->assertContains('repeater', array_column($this->catalogue()->types(), 'key'));
    }

    #[Test]
    public function it_offers_somebody_in_the_vault_then_every_kind_of_record_it_is_given(): void
    {
        $animals = new EntityType(['name_plural' => 'Animals']);
        $animals->id = 12;

        $targets = $this->catalogue()->relationTargets(new Collection([$animals]));

        $this->assertSame(['contact', 'entity:12'], array_keys($targets));
        $this->assertSame('Animals', $targets['entity:12']);
    }

    #[Test]
    public function it_names_the_supplied_list_of_labels_each_labelled_type_starts_from(): void
    {
        $email = new ValueList(['key' => ValueListEnum::EmailLabels->value]);
        $email->id = 4;
        $address = new ValueList(['key' => ValueListEnum::AddressLabels->value]);
        $address->id = 7;
        $homemade = new ValueList(['key' => null]);
        $homemade->id = 9;

        $this->assertEquals(
            ['email' => 4, 'address' => 7],
            $this->catalogue()->suppliedLabelListIds(collect([$homemade, $email, $address])),
        );
    }

    #[Test]
    public function it_flattens_the_families_in_the_order_they_are_shown(): void
    {
        $catalogue = $this->catalogue();
        $flattened = array_map(fn (array $type): string => $type['key'], $catalogue->types());

        $grouped = [];

        foreach ($catalogue->families() as $family) {
            foreach ($family['types'] as $type) {
                $grouped[] = $type['key'];
            }
        }

        $this->assertEquals($grouped, $flattened);
        $this->assertCount($catalogue->total(), $flattened);
    }

    #[Test]
    public function it_indexes_every_type_for_the_filter(): void
    {
        $index = $this->catalogue()->searchIndex();

        $this->assertCount($this->catalogue()->total(), $index);
        $this->assertStringContainsString('recurring_date', $index['recurring_date']);
        $this->assertEquals(mb_strtolower($index['recurring_date']), $index['recurring_date']);
    }

    #[Test]
    public function it_writes_the_count_line_once_for_every_count_it_could_show(): void
    {
        $catalogue = $this->catalogue();
        $phrases = $catalogue->countPhrases();

        $this->assertCount($catalogue->total() + 1, $phrases);
    }

    #[Test]
    public function it_names_the_section_and_the_type_in_the_footer(): void
    {
        $lines = $this->catalogue()->footerLines('Health');

        $this->assertCount($this->catalogue()->total(), $lines);
        $this->assertStringContainsString('Health', $lines['text']);
    }

    private function catalogue(): FieldCatalogueViewModel
    {
        return new FieldCatalogueViewModel;
    }
}
