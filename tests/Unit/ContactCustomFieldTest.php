<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactCustomFieldTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory('App\Account')->create([]);
        $contactCustomField = factory('App\ContactCustomField')->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($contactCustomField->account()->exists());
    }

    public function test_it_belongs_to_one_custom_field()
    {
        $customField = factory('App\CustomField')->create([]);
        $contactCustomField = factory('App\ContactCustomField')->create([
            'custom_field_id' => $customField->id,
        ]);

        $this->assertTrue($contactCustomField->customField()->exists());
    }

    public function test_it_belongs_to_one_contact()
    {
        $contact = factory('App\Contact')->create([]);
        $contactCustomField = factory('App\ContactCustomField')->create([
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($contactCustomField->contact()->exists());
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
}
