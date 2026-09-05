<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Templates;

use App\Enums\FieldTypeEnum;
use App\ViewModels\Settings\Account\Templates\FieldCatalogueViewModel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldCatalogueViewModelTest extends TestCase
{
    #[Test]
    public function it_describes_every_type_it_offers(): void
    {
        foreach ($this->catalogue()->families() as $family) {
            $this->assertNotEmpty($family['name']);
            $this->assertNotEmpty($family['blurb']);

            foreach ($family['types'] as $type) {
                $this->assertNotEmpty($type['label']);
                $this->assertNotEmpty($type['description']);
                $this->assertNotEmpty($type['example']);
            }
        }
    }

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
    public function it_says_which_types_need_a_value_list(): void
    {
        $needing = $this->catalogue()->typesNeedingAValueList();

        $this->assertContains('select', $needing);
        $this->assertContains('multiselect', $needing);
        $this->assertContains('radio', $needing);
        $this->assertNotContains('text', $needing);
    }

    #[Test]
    public function it_gives_every_type_a_mark_and_an_illustration(): void
    {
        $kinds = ['input', 'textarea', 'toggle', 'dropdown', 'list', 'pills', 'upload', 'rows', 'layout'];

        foreach ($this->catalogue()->types() as $type) {
            $this->assertNotEmpty($type['mark'], "{$type['key']} has no mark");
            $this->assertContains($type['preview']['kind'], $kinds, "{$type['key']} draws nothing known");
        }
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
        $this->assertStringContainsString('recurring date', $index['recurring_date']);
        $this->assertStringContainsString('recurring_date', $index['recurring_date']);
        $this->assertStringContainsString('birthday', $index['recurring_date']);
        $this->assertEquals(mb_strtolower($index['recurring_date']), $index['recurring_date']);
    }

    #[Test]
    public function it_writes_the_count_line_once_for_every_count_it_could_show(): void
    {
        $catalogue = $this->catalogue();
        $phrases = $catalogue->countPhrases();

        $this->assertCount($catalogue->total() + 1, $phrases);
        $this->assertEquals('1 of '.$catalogue->total().' type', $phrases[1]);
        $this->assertEquals('7 of '.$catalogue->total().' types', $phrases[7]);
        $this->assertEquals($catalogue->total().' types', $catalogue->totalPhrase());
    }

    #[Test]
    public function it_names_the_section_and_the_type_in_the_footer(): void
    {
        $lines = $this->catalogue()->footerLines('Health');

        $this->assertCount($this->catalogue()->total(), $lines);
        $this->assertStringContainsString('Health', $lines['text']);
        $this->assertStringContainsString('“Short text”', $lines['text']);
    }

    #[Test]
    public function it_says_in_one_sentence_what_the_panel_is_previewing(): void
    {
        $sentences = $this->catalogue()->sentences();

        $this->assertEquals('Short text. One line of text, up to a length you set.', $sentences['text']);
        $this->assertStringContainsString('Not available yet.', $sentences['money']);
    }

    private function catalogue(): FieldCatalogueViewModel
    {
        return new FieldCatalogueViewModel;
    }
}
