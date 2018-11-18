<?php

namespace Tests\Unit\Services\VCard;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Address;
use App\Models\Contact\Contact;
use Sabre\VObject\Component\VCard;
use App\Services\VCard\ExportVCard;
use App\Models\Contact\ContactField;
use Sabre\VObject\PHPUnitAssertions;
use App\Models\Contact\ContactFieldType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ExportVCardTest extends TestCase
{
    use DatabaseTransactions,
        PHPUnitAssertions;

    /** @var int */
    const defaultPropsCount = 3;

    public function test_vcard_add_names()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $vCard = new VCard();

        $exportVCard = new ExportVCard();
        $this->invokePrivateMethod($exportVCard, 'exportNames', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 2,
            $vCard->children()
        );
        $this->assertContains('FN:John Doe', $vCard->serialize());
        $this->assertContains('N:Doe;John;;;', $vCard->serialize());
    }

    public function test_vcard_add_nickname()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'nickname' => 'the nickname',
        ]);
        $vCard = new VCard();

        $exportVCard = new ExportVCard();
        $this->invokePrivateMethod($exportVCard, 'exportNames', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 3,
            $vCard->children()
        );
        $this->assertContains('FN:John Doe', $vCard->serialize());
        $this->assertContains('N:Doe;John;;;', $vCard->serialize());
        $this->assertContains('NICKNAME:the nickname', $vCard->serialize());
    }

    public function test_vcard_add_gender()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $vCard = new VCard();

        $exportVCard = new ExportVCard();
        $this->invokePrivateMethod($exportVCard, 'exportGender', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertContains('GENDER:O;', $vCard->serialize());
    }

    public function test_vcard_add_photo()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $vCard = new VCard();

        $contact->has_avatar = false;
        $contact->gravatar_url = 'gravatar';

        $exportVCard = new ExportVCard();
        $this->invokePrivateMethod($exportVCard, 'exportPhoto', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertContains('PHOTO;VALUE=URI:gravatar', $vCard->serialize());
    }

    public function test_vcard_add_work_org()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'company' => 'the company',
        ]);
        $vCard = new VCard();

        $exportVCard = new ExportVCard();
        $this->invokePrivateMethod($exportVCard, 'exportWorkInformation', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertContains('ORG:the company', $vCard->serialize());
    }

    public function test_vcard_add_work_title()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'job' => 'job position',
        ]);
        $vCard = new VCard();

        $exportVCard = new ExportVCard();
        $this->invokePrivateMethod($exportVCard, 'exportWorkInformation', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertContains('TITLE:job position', $vCard->serialize());
    }

    public function test_vcard_add_work_information()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'company' => 'the company',
            'job' => 'job position',
        ]);
        $vCard = new VCard();

        $exportVCard = new ExportVCard();
        $this->invokePrivateMethod($exportVCard, 'exportWorkInformation', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 2,
            $vCard->children()
        );
        $this->assertContains('ORG:the company', $vCard->serialize());
        $this->assertContains('TITLE:job position', $vCard->serialize());
    }

    public function test_vcard_add_birthday()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $contact->setSpecialDate('birthdate', 2000, 10, 5);
        $vCard = new VCard();

        $exportVCard = new ExportVCard();
        $this->invokePrivateMethod($exportVCard, 'exportBirthday', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertContains('BDAY:20001005', $vCard->serialize());
    }

    public function test_vcard_add_contact_fields_empty()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $vCard = new VCard();

        $exportVCard = new ExportVCard();
        $this->invokePrivateMethod($exportVCard, 'exportContactFields', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount,
            $vCard->children()
        );
    }

    public function test_vcard_add_contact_fields()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $vCard = new VCard();

        $contactFieldType = factory(ContactFieldType::class)->create(['account_id' => $account->id]);
        $contactField = factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $account->id,
            'contact_field_type_id' => $contactFieldType->id,
        ]);

        $exportVCard = new ExportVCard();
        $this->invokePrivateMethod($exportVCard, 'exportContactFields', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertContains('EMAIL:john@doe.com', $vCard->serialize());
    }

    public function test_vcard_add_addresses_empty()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $vCard = new VCard();

        $exportVCard = new ExportVCard();
        $this->invokePrivateMethod($exportVCard, 'exportAddress', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount,
            $vCard->children()
        );
    }

    public function test_vcard_add_addresses()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $vCard = new VCard();

        $contactFieldType = factory(Address::class)->create([
            'contact_id' => $contact->id,
            'name' => 'Home',
            'street' => '123 st',
            'city' => 'Montreal',
            'province' => 'Quebec',
            'account_id' => $account->id,
        ]);

        $contactFieldType = factory(Address::class)->create([
            'contact_id' => $contact->id,
            'name' => 'Home',
            'street' => '823 st',
            'city' => 'Montreal',
            'province' => 'Quebec',
            'account_id' => $account->id,
        ]);

        $exportVCard = new ExportVCard();
        $this->invokePrivateMethod($exportVCard, 'exportAddress', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 2,
            $vCard->children()
        );
        $this->assertContains('ADR:;;123 st;Montreal;Quebec;;', $vCard->serialize());
        $this->assertContains('ADR:;;823 st;Montreal;Quebec;;', $vCard->serialize());
    }

    public function test_vcard_prepares_an_almost_empty_vcard()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);

        $exportVCard = new ExportVCard();
        $vCard = $this->invokePrivateMethod($exportVCard, 'export', [$contact]);

        $this->assertCount(
            self::defaultPropsCount + 4,
            $vCard->children()
        );
        $hash = $contact->hashid();
        $url = config('app.url');
        $this->assertVObjectEqualsVObject("BEGIN:VCARD
VERSION:4.0
PRODID:-//Sabre//Sabre VObject 4.1.6//EN
UID:{$hash}
SOURCE:{$url}/people/{$hash}
FN:John Doe
N:Doe;John;;;
GENDER:O;
END:VCARD", $vCard);
    }

    public function test_vcard_prepares_a_complete_vcard()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);

        $contactFieldType = factory(Address::class)->create([
            'contact_id' => $contact->id,
            'name' => 'Home',
            'street' => '123 st',
            'city' => 'Montreal',
            'province' => 'Quebec',
            'account_id' => $account->id,
        ]);

        $contactFieldType = factory(Address::class)->create([
            'contact_id' => $contact->id,
            'name' => 'Home',
            'street' => '823 st',
            'city' => 'Montreal',
            'province' => 'Quebec',
            'account_id' => $account->id,
        ]);

        $contactFieldType = factory(ContactFieldType::class)->create(['account_id' => $account->id]);
        $contactField = factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $account->id,
            'contact_field_type_id' => $contactFieldType->id,
        ]);

        $exportVCard = new ExportVCard();
        $vCard = $this->invokePrivateMethod($exportVCard, 'export', [$contact]);

        $this->assertCount(
            self::defaultPropsCount + 7,
            $vCard->children()
        );
        $hash = $contact->hashid();
        $url = config('app.url');
        $this->assertVObjectEqualsVObject("BEGIN:VCARD
VERSION:4.0
PRODID:-//Sabre//Sabre VObject 4.1.6//EN
UID:{$hash}
SOURCE:{$url}/people/{$hash}
FN:John Doe
N:Doe;John;;;
GENDER:O;
ADR:;;123 st;Montreal;Quebec;;
ADR:;;823 st;Montreal;Quebec;;
EMAIL:john@doe.com
END:VCARD", $vCard);
    }
}
