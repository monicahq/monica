<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Account\Field;
use App\Models\Account\ContactFieldValue;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class FieldTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_a_custom_field()
    {
        $field = factory(Field::class)->create([]);
        $this->assertTrue($field->customField()->exists());
    }

    public function test_it_returns_the_value_of_the_field_for_the_given_contact()
    {
        $contactFieldValue = factory(ContactFieldValue::class)->create([
            'value' => 'this is a value',
        ]);
        $contact = $contactFieldValue->contact;
        $field = $contactFieldValue->field;

        $this->assertEquals(
            'this is a value',
            $field->getValueForContact($contact)
        );
    }
}
