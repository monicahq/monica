<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactCustomFieldPatternTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory('App\Account')->create([]);
        $contactCustomFieldPattern = factory('App\ContactCustomFieldPattern')->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($contactCustomFieldPattern->account()->exists());
    }

    public function test_it_belongs_to_one_custom_field_pattern()
    {
        $customFieldPattern = factory('App\CustomFieldPattern')->create([]);
        $contactCustomFieldPattern = factory('App\ContactCustomFieldPattern')->create([
            'custom_field_pattern_id' => $customFieldPattern->id,
        ]);

        $this->assertTrue($contactCustomFieldPattern->customFieldPattern()->exists());
    }

    public function test_it_belongs_to_one_contact()
    {
        $contact = factory('App\Contact')->create([]);
        $contactCustomFieldPattern = factory('App\ContactCustomFieldPattern')->create([
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($contactCustomFieldPattern->contact()->exists());
    }
}
