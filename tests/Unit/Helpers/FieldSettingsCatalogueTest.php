<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Helpers\FieldSettingsCatalogue;
use App\Models\Field;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldSettingsCatalogueTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_kind_of_field_answers(): void
    {
        foreach (FieldTypeEnum::cases() as $type) {
            $this->assertIsArray(FieldSettingsCatalogue::of($type));
        }
    }

    #[Test]
    public function every_setting_names_a_control_that_can_be_drawn(): void
    {
        foreach (FieldTypeEnum::cases() as $type) {
            foreach (FieldSettingsCatalogue::of($type) as $setting) {
                $this->assertContains($setting['control'], ['input', 'number', 'select']);
                $this->assertContains($setting['width'], ['narrow', 'medium', 'wide']);
                $this->assertNotSame('', $setting['label']);
            }
        }
    }

    #[Test]
    public function a_setting_kept_in_a_column_names_one_the_field_has(): void
    {
        $columns = Schema::getColumnListing('fields');

        foreach (FieldSettingsCatalogue::COLUMNS as $column) {
            $this->assertContains($column, $columns);
        }
    }

    #[Test]
    public function a_select_offers_something_to_pick(): void
    {
        foreach (FieldTypeEnum::cases() as $type) {
            foreach (FieldSettingsCatalogue::of($type) as $setting) {
                if ($setting['control'] === 'select') {
                    $this->assertNotSame([], $setting['options']);
                }
            }
        }
    }

    #[Test]
    public function a_relation_may_point_at_an_entity_type_of_the_account(): void
    {
        $settings = FieldSettingsCatalogue::of(FieldTypeEnum::Relation, [7 => 'Pet']);

        $this->assertSame(
            ['contact', 'entity:7'],
            array_column($settings[0]['options'], 'value'),
        );
    }

    #[Test]
    public function a_relation_points_at_somebody_when_the_account_has_named_no_entity_type(): void
    {
        $settings = FieldSettingsCatalogue::of(FieldTypeEnum::Relation);

        $this->assertSame(['contact'], array_column($settings[0]['options'], 'value'));
    }

    #[Test]
    public function it_reads_a_setting_back_out_of_the_field_that_holds_it(): void
    {
        $this->assertSame('120', FieldSettingsCatalogue::read('max_length', ['max_length' => 120], null));
        $this->assertSame('Dr Meyer', FieldSettingsCatalogue::read('placeholder', null, 'Dr Meyer'));
        $this->assertSame('', FieldSettingsCatalogue::read('unit', null, null));
    }

    #[Test]
    public function it_reads_a_setting_holding_several_things_as_one_line(): void
    {
        $this->assertSame(
            'image/png, application/pdf',
            FieldSettingsCatalogue::read('accepted_mime_types', ['accepted_mime_types' => ['image/png', 'application/pdf']], null),
        );
    }

    #[Test]
    public function it_reads_what_a_relation_points_at(): void
    {
        $this->assertSame([RelationTargetEnum::Contact, null], FieldSettingsCatalogue::target('contact'));
        $this->assertSame([RelationTargetEnum::Entity, 12], FieldSettingsCatalogue::target('entity:12'));
        $this->assertNull(FieldSettingsCatalogue::target('entity:'));
        $this->assertNull(FieldSettingsCatalogue::target('entity:dunder'));
        $this->assertNull(FieldSettingsCatalogue::target('mifflin'));
    }

    #[Test]
    public function it_splits_a_line_of_several_things_and_drops_the_blanks(): void
    {
        $this->assertSame(['image/png', 'application/pdf'], FieldSettingsCatalogue::commaSeparated(' image/png, ,application/pdf,'));
        $this->assertSame([], FieldSettingsCatalogue::commaSeparated(''));
    }

    #[Test]
    public function only_a_field_reading_from_a_list_offers_choices(): void
    {
        $this->assertTrue(FieldSettingsCatalogue::offersChoices(FieldTypeEnum::Select));
        $this->assertTrue(FieldSettingsCatalogue::offersChoices(FieldTypeEnum::Multiselect));
        $this->assertTrue(FieldSettingsCatalogue::offersChoices(FieldTypeEnum::Radio));
        $this->assertFalse(FieldSettingsCatalogue::offersChoices(FieldTypeEnum::Text));
    }

    #[Test]
    public function a_field_can_be_given_every_setting_its_kind_takes(): void
    {
        $fillable = (new Field)->getFillable();

        foreach (FieldSettingsCatalogue::COLUMNS as $column) {
            $this->assertContains($column, $fillable);
        }
    }
}
