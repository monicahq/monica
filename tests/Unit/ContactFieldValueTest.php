<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactFieldValueTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory('App\Account')->create([]);
        $contactFieldValue = factory('App\ContactFieldValue')->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($contactFieldValue->account()->exists());
    }

    public function test_it_belongs_to_one_custom_field()
    {
        $contactCustomField = factory('App\ContactCustomField')->create([]);
        $contactFieldValue = factory('App\ContactFieldValue')->create([
            'contact_custom_field_id' => $contactCustomField->id,
        ]);

        $this->assertTrue($contactFieldValue->contactCustomField()->exists());
    }

    public function test_it_belongs_to_one_contact()
    {
        $contact = factory('App\Contact')->create([]);
        $contactFieldValue = factory('App\ContactFieldValue')->create([
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($contactFieldValue->contact()->exists());
    }

    public function test_it_belongs_to_one_field()
    {
        $field = factory('App\Field')->create([]);
        $contactFieldValue = factory('App\ContactFieldValue')->create([
            'field_id' => $field->id,
        ]);

        $this->assertTrue($contactFieldValue->field()->exists());
    }

    public function test_it_retrieves_the_value()
    {
        $contactFieldValue = factory('App\ContactFieldValue')->create([
            'value' => 'Flirt',
        ]);

        $this->assertEquals(
            'Flirt',
            $contactFieldValue->value
        );
    }
}
