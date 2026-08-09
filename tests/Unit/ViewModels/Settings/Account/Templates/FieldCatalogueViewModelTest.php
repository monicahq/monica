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

    /**
     * The catalogue and the enum must not drift: a type the enum does not know
     * cannot be picked, and every type the enum knows has to be offered.
     */
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

    private function catalogue(): FieldCatalogueViewModel
    {
        return new FieldCatalogueViewModel;
    }
}
