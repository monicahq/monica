<?php

namespace Tests\Unit;

use App\Account;
use App\Contact;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Settings\CustomFields\CustomFieldPattern;
use App\Models\Settings\CustomFields\ContactCustomFieldPattern;

class ContactCustomFieldPatternTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $contactCustomFieldPattern = factory(ContactCustomFieldPattern::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($contactCustomFieldPattern->account()->exists());
    }

    public function test_it_belongs_to_one_custom_field_pattern()
    {
        $customFieldPattern = factory(CustomFieldPattern::class)->create([]);
        $contactCustomFieldPattern = factory(ContactCustomFieldPattern::class)->create([
            'custom_field_pattern_id' => $customFieldPattern->id,
        ]);

        $this->assertTrue($contactCustomFieldPattern->customFieldPattern()->exists());
    }

    public function test_it_belongs_to_one_contact()
    {
        $contact = factory(Contact::class)->create([]);
        $contactCustomFieldPattern = factory(ContactCustomFieldPattern::class)->create([
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($contactCustomFieldPattern->contact()->exists());
    }
}
