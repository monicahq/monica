<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\CustomField;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CustomFieldTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory('App\Account')->create([]);
        $customField = factory('App\CustomField')->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($customField->account()->exists());
    }

    public function test_it_has_many_fields()
    {
        $customField = factory('App\CustomField')->create([]);
        $field = factory('App\Field', 2)->create([
            'custom_field_id' => $customField->id,
        ]);

        $this->assertTrue($customField->fields()->exists());
    }

    public function test_it_retrieves_the_name_of_the_custom_field()
    {
        $customField = factory('App\CustomField')->make([
            'name' => 'Flirt',
        ]);

        $this->assertEquals(
            'Flirt',
            $customField->name
        );
    }

    public function test_it_retrieves_the_is_list_attribute()
    {
        $customField = factory('App\CustomField')->make([
            'is_list' => 0,
        ]);

        $this->assertEquals(
            0,
            $customField->is_list
        );
    }

    public function test_it_retrieves_the_is_important_attribute()
    {
        $customField = factory('App\CustomField')->make([
            'is_important' => 0,
        ]);

        $this->assertEquals(
            0,
            $customField->is_important
        );
    }
}
