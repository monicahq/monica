<?php

namespace Tests\Unit;

use App\Account;
use Tests\TestCase;
use App\Models\Settings\CustomFields\Field;
use App\Models\Settings\CustomFields\FieldChoice;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class FieldChoiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $field = factory(FieldChoice::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($field->account()->exists());
    }

    public function test_it_belongs_to_one_field()
    {
        $field = factory(Field::class)->create([]);
        $fieldChoice = factory(FieldChoice::class)->create([
            'field_id' => $field->id,
        ]);

        $this->assertTrue($fieldChoice->field()->exists());
    }

    public function test_it_retrieves_the_value()
    {
        $fieldChoice = factory(FieldChoice::class)->make([
            'value' => 'Flirt',
        ]);

        $this->assertEquals(
            'Flirt',
            $fieldChoice->value
        );
    }

    public function test_it_retrieves_the_is_default_attribute()
    {
        $fieldChoice = factory(FieldChoice::class)->make([
            'is_default' => 0,
        ]);

        $this->assertEquals(
            0,
            $fieldChoice->is_default
        );
    }
}
