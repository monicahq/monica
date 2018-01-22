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
        $contact = factory(\App\Contact::class)->create();

        // populate a bunch of contact fields and contact field types
        $contactFieldType = factory(\App\ContactFieldType::class)->create();
        $contactField = factory(\App\ContactField::class)->create(['contact_id' => $contact->id]);

        $contactFields = VCardHelper::getAllEntriesOfASpecificContactFieldType($contact, 'email');

        $this->assertEquals(
            1,
            count($contactFields)
        );
    }

    public function test_it_doesnt_fetch_any_contact_field_types()
    {
        $contact = factory(\App\Contact::class)->create();

        $contactFields = VCardHelper::getAllEntriesOfASpecificContactFieldType($contact, 'email');

        $this->assertNull(
            $contactFields
        );
    }

    public function test_it_doesnt_fetch_any_contact_fields()
    {
        $contact = factory(\App\Contact::class)->create();
        $contactFieldType = factory(\App\ContactFieldType::class)->create();

        $contactFields = VCardHelper::getAllEntriesOfASpecificContactFieldType($contact, 'email');

        $this->assertNull(
            $contactFields
        );
    }

    public function test_it_doesnt_add_contact_fields_in_vcard()
    {
        $contact = factory(\App\Contact::class)->create();
        $vCard = new VCard();

        $vCard = VCardHelper::addContactFieldEntriesInVCard($contact, $vCard, 'email');

        $this->assertNull(
            $vCard->getProperties()
        );
    }

    public function test_it_adds_contact_fields_in_vcard()
    {
        $contact = factory(\App\Contact::class)->create();
        $vCard = new VCard();

        $contactFieldType = factory(\App\ContactFieldType::class)->create();
        $contactField = factory(\App\ContactField::class)->create(['contact_id' => $contact->id]);

        $vCard = VCardHelper::addContactFieldEntriesInVCard($contact, $vCard, 'email');

        $this->assertEquals(
            1,
            count($vCard->getProperties())
        );
    }

    public function test_it_doesnt_add_addresses_in_vcard()
    {
        $contact = factory(\App\Contact::class)->create();
        $vCard = new VCard();

        $vCard = VCardHelper::addAddressToVCard($contact, $vCard);

        $this->assertNull(
            $vCard->getProperties()
        );
    }

    public function test_it_adds_addresses_in_vcard()
    {
        $contact = factory(\App\Contact::class)->create();
        $vCard = new VCard();

        $contactFieldType = factory(\App\Address::class)->create([
            'contact_id' => $contact->id,
            'name' => 'Home',
            'street' => '123 st',
            'city' => 'Montreal',
            'province' => 'Quebec',
        ]);

        $contactFieldType = factory(\App\Address::class)->create([
            'contact_id' => $contact->id,
            'name' => 'Home',
            'street' => '123 st',
            'city' => 'Montreal',
            'province' => 'Quebec',
        ]);

        $vCard = VCardHelper::addAddressToVCard($contact, $vCard);

        $this->assertEquals(
            2,
            count($vCard->getProperties())
        );
    }
}
