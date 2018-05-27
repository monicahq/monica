<?php

namespace Tests\Unit;

use App\Account;
use Tests\TestCase;
use App\Models\Settings\CustomFields;
use App\Models\Settings\CustomFields\Field;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Settings\CustomFields\DefaultCustomFieldType;

class FieldTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $field = factory(Field::class)->create([]);

        $this->assertTrue($field->account()->exists());
    }

    public function test_it_belongs_to_a_custom_field_type()
    {
        $field = factory(Field::class)->create([]);

        $this->assertTrue($field->defaultCustomFieldType()->exists());
    }

    public function test_it_belongs_to_a_custom_field()
    {
        $field = factory(Field::class)->create([]);

        $this->assertTrue($field->customField()->exists());
    }

    public function test_it_retrieves_the_name_of_the_field()
    {
        $field = factory(Field::class)->make([
            'name' => 'Flirt',
        ]);

        $this->assertEquals(
            'Flirt',
            $field->name
        );
    }

    public function test_it_retrieves_the_is_required_attribute()
    {
        $field = factory(Field::class)->make([
            'is_required' => 0,
        ]);

        $this->assertEquals(
            0,
            $field->is_required
        );
    }

    public function test_it_retrieves_the_description_attribute()
    {
        $field = factory(Field::class)->make([
            'description' => 'this is a description',
        ]);

        $this->assertEquals(
            'this is a description',
            $field->description
        );
    }
}
