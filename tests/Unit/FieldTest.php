<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class FieldTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory('App\Account')->create([]);
        $field = factory('App\Field')->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($field->account()->exists());
    }

    public function test_it_belongs_to_a_custom_field_type()
    {
        $customFieldType = factory('App\CustomFieldType')->create([]);
        $field = factory('App\Field')->create([
            'custom_field_type_id' => $customFieldType->id,
        ]);

        $this->assertTrue($field->customFieldType()->exists());
    }

    public function test_it_belongs_to_a_custom_field()
    {
        $customField = factory('App\CustomField')->create([]);
        $field = factory('App\Field')->create([
            'custom_field_id' => $customField->id,
        ]);

        $this->assertTrue($field->customField()->exists());
    }

    public function test_it_retrieves_the_name_of_the_field()
    {
        $field = factory('App\Field')->make([
            'name' => 'Flirt',
        ]);

        $this->assertEquals(
            'Flirt',
            $field->name
        );
    }

    public function test_it_retrieves_the_is_required_attribute()
    {
        $field = factory('App\Field')->make([
            'is_required' => 0,
        ]);

        $this->assertEquals(
            0,
            $field->is_required
        );
    }
}
