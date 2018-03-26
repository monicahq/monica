<?php

namespace Tests\Helper;

use App\Contact;
use Tests\FeatureTestCase;
use App\Helpers\VCardHelper;
use JeroenDesloovere\VCard\VCard;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class VCardHelperTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_it_fetches_all_contact_fields()
    {
        $account = factory(\App\Account::class)->create();
        $contact = factory(\App\Contact::class)->create(['account_id' => $account->id]);

        // populate a bunch of contact fields and contact field types
        $contactFieldType = factory(\App\ContactFieldType::class)->create(['account_id' => $account->id]);
        $contactField = factory(\App\ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $account->id,
            'contact_field_type_id' => $contactFieldType->id,
        ]);

        $contactFields = VCardHelper::getAllEntriesOfASpecificContactFieldType($contact, 'email');

        $this->assertCount(
            1,
            $contactFields
        );
    }

    public function test_it_doesnt_fetch_any_contact_field_types()
    {
        $account = factory(\App\Account::class)->create();
        $contact = factory(\App\Contact::class)->create(['account_id' => $account->id]);

        $contactFields = VCardHelper::getAllEntriesOfASpecificContactFieldType($contact, 'email');

        $this->assertNull(
            $contactFields
        );
    }

    public function test_it_doesnt_fetch_any_contact_fields()
    {
        $account = factory(\App\Account::class)->create();
        $contact = factory(\App\Contact::class)->create(['account_id' => $account->id]);
        $contactFieldType = factory(\App\ContactFieldType::class)->create(['account_id' => $account->id]);

        $contactFields = VCardHelper::getAllEntriesOfASpecificContactFieldType($contact, 'email');

        $this->assertNull(
            $contactFields
        );
    }

    public function test_it_doesnt_add_contact_fields_in_vcard()
    {
        $account = factory(\App\Account::class)->create();
        $contact = factory(\App\Contact::class)->create(['account_id' => $account->id]);
        $vCard = new VCard();

        $vCard = VCardHelper::addContactFieldEntriesInVCard($contact, $vCard, 'email');

        $this->assertNull(
            $vCard->getProperties()
        );
    }

    public function test_it_adds_contact_fields_in_vcard()
    {
        $account = factory(\App\Account::class)->create();
        $contact = factory(\App\Contact::class)->create(['account_id' => $account->id]);
        $vCard = new VCard();

        $contactFieldType = factory(\App\ContactFieldType::class)->create(['account_id' => $account->id]);
        $contactField = factory(\App\ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $account->id,
            'contact_field_type_id' => $contactFieldType->id,
        ]);

        $vCard = VCardHelper::addContactFieldEntriesInVCard($contact, $vCard, 'email');

        $this->assertCount(
            1,
            $vCard->getProperties()
        );
    }

    public function test_it_doesnt_add_addresses_in_vcard()
    {
        $account = factory(\App\Account::class)->create();
        $contact = factory(\App\Contact::class)->create(['account_id' => $account->id]);
        $vCard = new VCard();

        $vCard = VCardHelper::addAddressToVCard($contact, $vCard);

        $this->assertNull(
            $vCard->getProperties()
        );
    }

    public function test_it_adds_addresses_in_vcard()
    {
        $account = factory(\App\Account::class)->create();
        $contact = factory(\App\Contact::class)->create(['account_id' => $account->id]);
        $vCard = new VCard();

        $contactFieldType = factory(\App\Address::class)->create([
            'contact_id' => $contact->id,
            'name' => 'Home',
            'street' => '123 st',
            'city' => 'Montreal',
            'province' => 'Quebec',
            'account_id' => $account->id,
        ]);

        $contactFieldType = factory(\App\Address::class)->create([
            'contact_id' => $contact->id,
            'name' => 'Home',
            'street' => '123 st',
            'city' => 'Montreal',
            'province' => 'Quebec',
            'account_id' => $account->id,
        ]);

        $vCard = VCardHelper::addAddressToVCard($contact, $vCard);

        $this->assertCount(
            2,
            $vCard->getProperties()
        );
    }

    public function test_it_prepares_an_almost_empty_vcard()
    {
        $account = factory(\App\Account::class)->create();
        $contact = factory(\App\Contact::class)->create(['account_id' => $account->id]);

        $vCard = VCardHelper::prepareVCard($contact);

        $this->assertEquals(
            'John Doe',
            $vCard->getProperties()[1]['value']
        );

        $this->assertCount(
            5,
            $vCard->getProperties()
        );
    }

    public function test_it_prepares_an_complete_vcard()
    {
        $account = factory(\App\Account::class)->create();
        $contact = factory(\App\Contact::class)->create(['account_id' => $account->id]);

        $contactFieldType = factory(\App\Address::class)->create([
            'contact_id' => $contact->id,
            'name' => 'Home',
            'street' => '123 st',
            'city' => 'Montreal',
            'province' => 'Quebec',
            'account_id' => $account->id,
        ]);

        $contactFieldType = factory(\App\Address::class)->create([
            'contact_id' => $contact->id,
            'name' => 'Home',
            'street' => '123 st',
            'city' => 'Montreal',
            'province' => 'Quebec',
            'account_id' => $account->id,
        ]);

        $contactFieldType = factory(\App\ContactFieldType::class)->create(['account_id' => $account->id]);
        $contactField = factory(\App\ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $account->id,
            'contact_field_type_id' => $contactFieldType->id,
        ]);

        $vCard = VCardHelper::prepareVCard($contact);

        $this->assertCount(
            8,
            $vCard->getProperties()
        );
    }
}
