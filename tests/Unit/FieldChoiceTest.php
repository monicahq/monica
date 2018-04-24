<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class FieldChoiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory('App\Account')->create([]);
        $field = factory('App\FieldChoice')->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($field->account()->exists());
    }

    public function test_it_belongs_to_one_field()
    {
        $field = factory('App\Field')->create([]);
        $fieldChoice = factory('App\FieldChoice')->create([
            'field_id' => $field->id,
        ]);

        $this->assertTrue($fieldChoice->field()->exists());
    }

    public function test_it_retrieves_the_value()
    {
        $fieldChoice = factory('App\FieldChoice')->make([
            'value' => 'Flirt',
        ]);

        $this->assertEquals(
            'Flirt',
            $fieldChoice->value
        );
    }

    public function test_it_retrieves_the_is_default_attribute()
    {
        $fieldChoice = factory('App\FieldChoice')->make([
            'is_default' => 0,
        ]);

        $this->assertEquals(
            0,
            $fieldChoice->is_default
        );
    }
}
