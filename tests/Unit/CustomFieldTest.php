<?php

namespace Tests\Unit;

use App\Account;
use Tests\TestCase;
use App\Models\Settings\CustomFields\Field;
use App\Models\Settings\CustomFields\CustomField;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Settings\CustomFields\CustomFieldPattern;

class CustomFieldTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $customField = factory(CustomField::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($customField->account()->exists());
    }

    public function test_it_has_many_fields()
    {
        $customField = factory(CustomField::class)->create([]);
        $field = factory(Field::class, 2)->create([
            'custom_field_id' => $customField->id,
        ]);

        $this->assertTrue($customField->fields()->exists());
    }

    public function test_it_belongs_to_a_custom_field_pattern()
    {
        $account = factory(Account::class)->create([]);
        $customFieldPattern = factory(CustomFieldPattern::class)->create([
            'account_id' => $account->id,
        ]);

        $customField = factory(CustomField::class)->create([
            'account_id' => $account->id,
            'custom_field_pattern_id' => $account->id,
        ]);

        $this->assertTrue($customField->customFieldPattern()->exists());
    }

    public function test_it_retrieves_the_name_of_the_custom_field()
    {
        $customField = factory(CustomField::class)->make([
            'name' => 'Flirt',
        ]);

        $this->assertEquals(
            'Flirt',
            $customField->name
        );
    }

    public function test_it_retrieves_the_is_list_attribute()
    {
        $customField = factory(CustomField::class)->make([
            'is_list' => 0,
        ]);

        $this->assertEquals(
            0,
            $customField->is_list
        );
    }

    public function test_it_retrieves_the_is_important_attribute()
    {
        $customField = factory(CustomField::class)->make([
            'is_important' => 0,
        ]);

        $this->assertEquals(
            0,
            $customField->is_important
        );
    }

    public function test_it_retrieves_the_field_order_attribute()
    {
        $customField = factory(CustomField::class)->make([
            'fields_order' => '1;3;4;1;4',
        ]);

        $this->assertEquals(
            '1;3;4;1;4',
            $customField->fields_order
        );
    }
}
