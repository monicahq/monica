<?php

namespace Tests\Unit\Services\VCard;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User\User;
use Sabre\VObject\Reader;
use App\Models\Contact\Tag;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Account\Account;
use App\Models\Contact\Address;
use App\Models\Contact\Contact;
use Sabre\VObject\Component\VCard;
use App\Models\Account\AddressBook;
use App\Services\VCard\ImportVCard;
use App\Models\Contact\ContactField;
use Sabre\VObject\PHPUnitAssertions;
use App\Models\Contact\ContactFieldType;
use App\Models\Contact\ContactFieldLabel;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ImportVCardTest extends TestCase
{
    use DatabaseTransactions,
        PHPUnitAssertions;

    /** @test */
    public function it_can_not_import_because_no_firstname_or_nickname_in_vcard()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard;

        $vcard = new VCard([]);

        $this->assertFalse($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_can_not_import_because_no_firstname_in_vcard()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'N'   => ['John', '', '', '', ''],
        ]);

        $this->assertFalse($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_can_not_import_because_empty_firstname_in_vcard()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'N'   => ';;;;',
        ]);

        $this->assertFalse($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_can_not_import_vcard()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard;

        $vcard = Reader::read('
BEGIN:VCARD
VERSION:3.0
N:;;;;
FN:
ORG:;
EMAIL;TYPE=home;TYPE=pref:mail@example.org
NOTE:
NICKNAME:
TITLE:
REV:20210900T000102Z
END:VCARD', Reader::OPTION_FORGIVING + Reader::OPTION_IGNORE_INVALID_LINES);

        $this->assertFalse($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_can_not_import_because_empty_nickname_in_vcard()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'NICKNAME'   => '',
        ]);

        $this->assertFalse($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_can_not_import_because_empty_fullname_in_vcard()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'FN'   => '',
        ]);

        $this->assertFalse($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_can_import_firstname()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'N'   => ['', 'John', '', '', ''],
        ]);

        $this->assertTrue($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_can_import_nickname()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'NICKNAME'   => 'John',
        ]);

        $this->assertTrue($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_can_import_fullname()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'FN'   => 'John Doe',
        ]);

        $this->assertTrue($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_validates_email()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard;

        $invalidEmail = 'test@';

        $this->assertFalse($this->invokePrivateMethod($importVCard, 'isValidEmail', [$invalidEmail]));

        $validEmail = 'john@doe.com';

        $this->assertTrue($this->invokePrivateMethod($importVCard, 'isValidEmail', [$validEmail]));
    }

    /** @test */
    public function it_checks_if_a_contact_exists()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard;
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $account->id,
            'type' => 'email',
        ]);
        $contactField = factory(ContactField::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'contact_field_type_id' => $contactFieldType->id,
            'data' => 'john@doe.com',
        ]);

        $vcard = new VCard([
            'N' => ['John', 'Doe', '', '', ''],
            'EMAIL' => 'john@',
        ]);

        $contact = $this->invokePrivateMethod($importVCard, 'getExistingContact', [$vcard]);
        $this->assertNull($contact);
    }

    /** @test */
    public function it_returns_an_unknown_name_if_no_name_is_in_entry()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'EMAIL' => 'john@',
        ]);

        $this->assertEquals(
            trans('settings.import_vcard_unknown_entry'),
            $this->invokePrivateMethod($importVCard, 'name', [$vcard])
        );
    }

    /** @test */
    public function it_returns_a_name_for_N()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'N' => ['John', 'Doe', '', '', ''],
            'EMAIL' => 'john@doe.com',
        ]);

        $this->assertEquals('Doe John john@doe.com', $this->invokePrivateMethod($importVCard, 'name', [$vcard]));
    }

    /** @test */
    public function it_returns_a_name_for_N_incomplete()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'N' => ['John', 'Doe'],
            'EMAIL' => 'john@doe.com',
        ]);

        $this->assertEquals('Doe John john@doe.com', $this->invokePrivateMethod($importVCard, 'name', [$vcard]));
    }

    /** @test */
    public function it_returns_a_name_for_NICKNAME()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'NICKNAME' => 'John',
            'EMAIL' => 'john@doe.com',
        ]);

        $this->assertEquals('John john@doe.com', $this->invokePrivateMethod($importVCard, 'name', [$vcard]));
    }

    /** @test */
    public function it_returns_a_name_for_FN()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'FN' => 'John Doe',
            'EMAIL' => 'john@doe.com',
        ]);

        $this->assertEquals('John Doe john@doe.com', $this->invokePrivateMethod($importVCard, 'name', [$vcard]));
    }

    /** @test */
    public function it_formats_value()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard;

        $result = $this->invokePrivateMethod($importVCard, 'formatValue', ['']);
        $this->assertNull($result);

        $result = $this->invokePrivateMethod($importVCard, 'formatValue', ['This is a value']);
        $this->assertEquals(
            'This is a value',
            $result
        );
    }

    /** @test */
    public function it_creates_a_contact()
    {
        $user = factory(User::class)->create([]);
        $importVCard = new ImportVCard;
        $importVCard->accountId = $user->account_id;
        $importVCard->userId = $user->id;

        $vcard = new VCard([
            'N' => ['John', 'Doe', '', '', ''],
            'EMAIL' => 'john@doe.com',
        ]);
        factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
            'type' => 'email',
        ]);

        $contact = $this->invokePrivateMethod($importVCard, 'importEntry', [null, $vcard, $vcard->serialize(), null]);

        $this->assertTrue($contact->exists);
    }

    /** @test */
    public function it_creates_a_contact_in_address_book()
    {
        $user = factory(User::class)->create([]);
        $addressBook = AddressBook::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'name' => 'contacts',
        ]);

        $importVCard = new ImportVCard;
        $importVCard->accountId = $user->account_id;
        $importVCard->userId = $user->id;

        $this->setPrivateValue($importVCard, 'addressBook', $addressBook);

        $vcard = new VCard([
            'N' => ['John', 'Doe', '', '', ''],
            'EMAIL' => 'john@doe.com',
        ]);
        factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
            'type' => 'email',
        ]);

        $contact = $this->invokePrivateMethod($importVCard, 'importEntry', [null, $vcard, $vcard->serialize(), null]);

        $this->assertTrue($contact->exists);
        $this->assertEquals($addressBook->id, $contact->address_book_id);
    }

    /** @test */
    public function it_update_a_contact_with_birthdate()
    {
        $user = factory(User::class)->create([]);
        $importVCard = new ImportVCard;
        $importVCard->accountId = $user->account_id;
        $importVCard->userId = $user->id;

        $vcard = new VCard([
            'N' => ['John', 'Doe', '', '', ''],
        ]);
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $contact->setSpecialDate('birthdate', 2001, 04, 01);

        $newContact = $this->invokePrivateMethod($importVCard, 'importGeneralInformation', [$contact, $vcard]);

        $this->assertEquals('John', $newContact->last_name);
        $this->assertEquals('Doe', $newContact->first_name);
        $this->assertNotNull($newContact->birthdate);
        $this->assertEquals('2001-04-01', $newContact->birthdate->date->format('Y-m-d'));
    }

    /** @test */
    public function it_update_a_contact_with_birthdate_age_based()
    {
        Carbon::setTestNow(Carbon::create(2021, 8, 25, 7, 0, 0));

        $user = factory(User::class)->create([]);
        $importVCard = new ImportVCard;
        $importVCard->accountId = $user->account_id;
        $importVCard->userId = $user->id;

        $vcard = new VCard([
            'N' => ['John', 'Doe', '', '', ''],
        ]);
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $contact->setSpecialDateFromAge('birthdate', 19);

        $newContact = $this->invokePrivateMethod($importVCard, 'importGeneralInformation', [$contact, $vcard]);

        $this->assertEquals('John', $newContact->last_name);
        $this->assertEquals('Doe', $newContact->first_name);
        $this->assertNotNull($newContact->birthdate);
        $this->assertTrue($newContact->birthdate->is_age_based);
        $this->assertEquals('2002-01-01', $newContact->birthdate->date->format('Y-m-d'));
    }

    /** @test */
    public function it_update_a_contact_with_birthdate_and_replace_it()
    {
        $user = factory(User::class)->create([]);
        $importVCard = new ImportVCard;
        $importVCard->accountId = $user->account_id;
        $importVCard->userId = $user->id;

        $vcard = new VCard([
            'N' => ['John', 'Doe', '', '', ''],
            'BDAY' => '1990-01-01',
        ]);
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $contact->setSpecialDate('birthdate', 2001, 04, 01);

        $newContact = $this->invokePrivateMethod($importVCard, 'importGeneralInformation', [$contact, $vcard]);

        $this->assertEquals('John', $newContact->last_name);
        $this->assertEquals('Doe', $newContact->first_name);
        $this->assertNotNull($newContact->birthdate);
        $this->assertEquals('1990-01-01', $newContact->birthdate->date->format('Y-m-d'));
    }

    /** @test */
    public function it_update_a_contact_with_deceased_date()
    {
        $user = factory(User::class)->create([]);
        $importVCard = new ImportVCard;
        $importVCard->accountId = $user->account_id;
        $importVCard->userId = $user->id;

        $vcard = new VCard([
            'N' => ['John', 'Doe', '', '', ''],
        ]);
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
            'is_dead' => true,
        ]);
        $contact->setSpecialDate('deceased_date', 2021, 07, 01);

        $newContact = $this->invokePrivateMethod($importVCard, 'importGeneralInformation', [$contact, $vcard]);

        $this->assertEquals('John', $newContact->last_name);
        $this->assertEquals('Doe', $newContact->first_name);
        $this->assertTrue($newContact->is_dead);
        $this->assertNotNull($newContact->deceasedDate);
        $this->assertEquals('2021-07-01', $newContact->deceasedDate->date->format('Y-m-d'));
    }

    /** @test */
    public function it_creates_a_contact_with_process()
    {
        $user = factory(User::class)->create([]);
        $importVCard = new ImportVCard;
        $importVCard->accountId = $user->account_id;
        $importVCard->userId = $user->id;

        $vcard = new VCard([
            'N' => ['John', 'Doe', '', '', ''],
            'EMAIL' => 'john@doe.com',
        ]);
        factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
            'type' => 'email',
        ]);

        $result = $this->invokePrivateMethod($importVCard, 'processEntry', [
            ['behaviour' => 'behaviour_add'],
            $vcard,
            $vcard->serialize(),
        ]);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account_id,
            'id' => $result['contact_id'],
        ]);
    }

    /** @test */
    public function it_updates_a_contact_with_process()
    {
        $user = factory(User::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $importVCard = new ImportVCard;
        $importVCard->accountId = $user->account_id;
        $importVCard->userId = $user->id;

        $vcard = new VCard([
            'N' => ['Miles', 'Davis', '', '', ''],
        ]);

        $this->invokePrivateMethod($importVCard, 'processEntry', [
            [
                'behaviour' => 'behaviour_replace',
                'contact_id' => $contact->id,
            ],
            $vcard,
            $vcard->serialize(),
        ]);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account_id,
            'id' => $contact->id,
        ]);
        $contact->refresh();

        $this->assertEquals('Davis', $contact->first_name);
        $this->assertEquals('Miles', $contact->last_name);
    }

    /** @test */
    public function it_imports_names_N()
    {
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'N' => ['Doe', 'John', 'Jane', '', ''],
        ]);
        $contact = $this->invokePrivateMethod($importVCard, 'importNames', [[], $vcard]);

        $this->assertEquals('John', $contact['first_name']);
        $this->assertEquals('Doe', $contact['last_name']);
        $this->assertEquals('Jane', $contact['middle_name']);
    }

    /** @test */
    public function it_imports_names_NICKNAME()
    {
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'NICKNAME' => 'John',
        ]);
        $contact = $this->invokePrivateMethod($importVCard, 'importNames', [[], $vcard]);

        $this->assertEquals('John', $contact['first_name']);
    }

    /** @test */
    public function it_imports_names_FN()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create(['account_id' => $account->id]);
        $importVCard = new ImportVCard;
        $importVCard->accountId = $account->id;
        $importVCard->userId = $user->id;

        $vcard = new VCard([
            'FN' => 'John Doe',
        ]);
        $contact = $this->invokePrivateMethod($importVCard, 'importNames', [[], $vcard]);

        $this->assertEquals('John', $contact['first_name']);
        $this->assertEquals('Doe', $contact['last_name']);
    }

    /** @test */
    public function it_imports_names_FN_last()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
            'name_order' => 'lastname_firstname',
        ]);
        $importVCard = new ImportVCard;
        $importVCard->accountId = $account->id;
        $importVCard->userId = $user->id;

        $vcard = new VCard([
            'FN' => 'John Doe',
        ]);
        $contact = $this->invokePrivateMethod($importVCard, 'importNames', [[], $vcard]);

        $this->assertEquals('Doe', $contact['first_name']);
        $this->assertEquals('John', $contact['last_name']);
    }

    /** @test */
    public function it_imports_names_FN_extra_space()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create(['account_id' => $account->id]);
        $importVCard = new ImportVCard;
        $importVCard->accountId = $account->id;
        $importVCard->userId = $user->id;

        $vcard = new VCard([
            'FN' => 'John  Doe',
        ]);
        $contact = $this->invokePrivateMethod($importVCard, 'importNames', [[], $vcard]);

        $this->assertEquals('John', $contact['first_name']);
        $this->assertEquals('Doe', $contact['last_name']);
    }

    /** @test */
    public function it_imports_name_FN()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create(['account_id' => $account->id]);
        $importVCard = new ImportVCard;
        $importVCard->accountId = $account->id;
        $importVCard->userId = $user->id;

        $vcard = new VCard([
            'FN' => 'John',
            'N' => 'Mike;;;;',
        ]);
        $contact = $this->invokePrivateMethod($importVCard, 'importNames', [[], $vcard]);

        $this->assertEquals('John', $contact['first_name']);
        $this->assertEquals('', Arr::get($contact, 'last_name'));
    }

    /** @test */
    public function it_imports_name_FN_last()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
            'name_order' => 'lastname_firstname',
        ]);
        $importVCard = new ImportVCard;
        $importVCard->accountId = $account->id;
        $importVCard->userId = $user->id;

        $vcard = new VCard([
            'FN' => 'John',
            'N' => 'Mike;;;;',
        ]);
        $contact = $this->invokePrivateMethod($importVCard, 'importNames', [[], $vcard]);

        $this->assertEquals('John', $contact['first_name']);
        $this->assertEquals('', Arr::get($contact, 'last_name'));
    }

    /** @test */
    public function it_imports_names_FN_multiple()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create(['account_id' => $account->id]);
        $importVCard = new ImportVCard;
        $importVCard->accountId = $account->id;
        $importVCard->userId = $user->id;

        $vcard = new VCard([
            'FN' => 'John Doe Marco',
            'N' => 'Mike;;;;',
        ]);
        $contact = $this->invokePrivateMethod($importVCard, 'importNames', [[], $vcard]);

        $this->assertEquals('John', $contact['first_name']);
        $this->assertEquals('Doe Marco', $contact['last_name']);
    }

    /** @test */
    public function it_imports_work_information()
    {
        $user = factory(User::class)->create();
        $importVCard = new ImportVCard;
        $importVCard->accountId = $user->account_id;
        $importVCard->userId = $user->id;

        $vcard = new VCard([
            'ORG' => 'Company',
            'ROLE' => 'Branleur',
        ]);

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $this->invokePrivateMethod($importVCard, 'importWorkInformation', [$contact, $vcard]);

        $contact->refresh();
        $this->assertEquals(
            'Company',
            $contact->company
        );

        $this->assertEquals(
            'Branleur',
            $contact->job
        );
    }

    /** @test */
    public function it_imports_birthday()
    {
        config(['monica.requires_subscription' => false]);

        $user = factory(User::class)->create();
        $importVCard = new ImportVCard;
        $importVCard->accountId = $user->account_id;
        $importVCard->userId = $user->id;

        $vcard = new VCard([
            'BDAY' => '1990-01-01',
        ]);

        $contact = $this->invokePrivateMethod($importVCard, 'importBirthday', [[], $vcard]);

        $this->assertEquals([
            'is_birthdate_known' => true,
            'birthdate_is_age_based' => false,
            'birthdate_day' => 1,
            'birthdate_month' => 1,
            'birthdate_year' => 1990,
            'birthdate_add_reminder' => true,
            'is_deceased' => false,
        ], $contact);
    }

    /** @test */
    public function it_imports_birthday_compact_format()
    {
        config(['monica.requires_subscription' => false]);

        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'BDAY' => '19900101',
        ]);

        $contact = $this->invokePrivateMethod($importVCard, 'importBirthday', [[], $vcard]);

        $this->assertEquals([
            'is_birthdate_known' => true,
            'birthdate_is_age_based' => false,
            'birthdate_day' => 1,
            'birthdate_month' => 1,
            'birthdate_year' => 1990,
            'birthdate_add_reminder' => true,
            'is_deceased' => false,
        ], $contact);
    }

    /** @test */
    public function it_imports_birthday_year_unknown()
    {
        config(['monica.requires_subscription' => false]);

        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'BDAY' => '--05-22',
        ]);

        $contact = $this->invokePrivateMethod($importVCard, 'importBirthday', [[], $vcard]);

        $this->assertEquals([
            'is_birthdate_known' => true,
            'birthdate_is_age_based' => false,
            'birthdate_day' => 22,
            'birthdate_month' => 5,
            'birthdate_year' => null,
            'birthdate_add_reminder' => true,
            'is_deceased' => false,
        ], $contact);
    }

    /** @test */
    public function import_vcard_imports_address()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'ADR' => [
                '',
                '',
                'street',
                'CITY',
                'province',
                '10000',
                'us',
            ],
        ]);

        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $this->invokePrivateMethod($importVCard, 'importAddress', [$contact, $vcard]);

        $this->assertDatabaseHas('addresses', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);
        $this->assertDatabaseHas('places', [
            'account_id' => $account->id,
            'street' => 'street',
            'city' => 'CITY',
            'province' => 'province',
            'postal_code' => '10000',
            'country' => 'US',
        ]);
    }

    /** @test */
    public function import_vcard_imports_partial_address()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'ADR' => [
                '',
                '',
                'street',
            ],
        ]);

        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $this->invokePrivateMethod($importVCard, 'importAddress', [$contact, $vcard]);

        $this->assertDatabaseHas('addresses', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);
        $this->assertDatabaseHas('places', [
            'account_id' => $account->id,
            'street' => 'street',
            'city' => null,
            'province' => null,
            'postal_code' => null,
            'country' => null,
        ]);
    }

    /** @test */
    public function import_vcard_updates_address()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $address = factory(Address::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'ADR' => [
                '',
                '',
                'street',
                'CITY',
                'province',
                '10000',
                'us',
            ],
        ]);

        $this->invokePrivateMethod($importVCard, 'importAddress', [$contact, $vcard]);

        $this->assertDatabaseHas('addresses', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'id' => $address->id,
        ]);
        $this->assertDatabaseHas('places', [
            'account_id' => $account->id,
            'street' => 'street',
            'city' => 'CITY',
            'province' => 'province',
            'postal_code' => '10000',
            'country' => 'US',
        ]);
        $address->refresh();
        $place = $address->place()->first();
        $this->assertEquals($place->street, 'street');
        $this->assertEquals($place->city, 'CITY');
        $this->assertEquals($place->province, 'province');
        $this->assertEquals($place->postal_code, '10000');
        $this->assertEquals($place->country, 'US');
    }

    /** @test */
    public function import_vcard_updates_and_destroy_address()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $address1 = factory(Address::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);
        $address2 = factory(Address::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'ADR' => [
                '',
                '',
                'street',
                'CITY',
                'province',
                '10000',
                'us',
            ],
        ]);

        $this->invokePrivateMethod($importVCard, 'importAddress', [$contact, $vcard]);

        $this->assertDatabaseHas('addresses', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'id' => $address1->id,
        ]);
        $this->assertDatabaseMissing('addresses', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'id' => $address2->id,
        ]);
        $this->assertDatabaseHas('places', [
            'account_id' => $account->id,
            'street' => 'street',
            'city' => 'CITY',
            'province' => 'province',
            'postal_code' => '10000',
            'country' => 'US',
        ]);
        $address1->refresh();
        $place = $address1->place()->first();
        $this->assertEquals($place->street, 'street');
        $this->assertEquals($place->city, 'CITY');
        $this->assertEquals($place->province, 'province');
        $this->assertEquals($place->postal_code, '10000');
        $this->assertEquals($place->country, 'US');
    }

    /** @test */
    public function import_vcard_imports_email()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard;
        $importVCard->accountId = $account->id;

        $vcard = new VCard([
            'EMAIL' => 'john@doe.com',
        ]);

        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $account->id,
            'type' => 'email',
        ]);
        $this->invokePrivateMethod($importVCard, 'importEmail', [$contact, $vcard]);

        $this->assertDatabaseHas('contact_fields', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'data' => 'john@doe.com',
        ]);
    }

    /** @test */
    public function import_vcard_updates_email()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $email = factory(ContactField::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $importVCard = new ImportVCard;
        $importVCard->accountId = $account->id;

        $vcard = new VCard([
            'EMAIL' => 'other@doe.com',
        ]);

        $this->invokePrivateMethod($importVCard, 'importEmail', [$contact, $vcard]);

        $this->assertDatabaseHas('contact_fields', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'data' => 'other@doe.com',
        ]);
        $email->refresh();
        $this->assertEquals($email->data, 'other@doe.com');
    }

    /** @test */
    public function import_vcard_updates_and_detroy_email()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $email1 = factory(ContactField::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);
        factory(ContactField::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'data' => 'xxx@mail.com',
        ]);

        $importVCard = new ImportVCard;
        $importVCard->accountId = $account->id;

        $vcard = new VCard([
            'EMAIL' => 'other@doe.com',
        ]);

        $this->invokePrivateMethod($importVCard, 'importEmail', [$contact, $vcard]);

        $this->assertDatabaseHas('contact_fields', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'data' => 'other@doe.com',
        ]);
        $this->assertDatabaseMissing('contact_fields', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'data' => 'xxx@mail.com',
        ]);
        $email1->refresh();
        $this->assertEquals($email1->data, 'other@doe.com');
    }

    /** @test */
    public function it_imports_phone()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        factory(ContactFieldType::class)->create([
            'account_id' => $account->id,
            'type' => 'phone',
        ]);

        $importVCard = new ImportVCard;
        $importVCard->accountId = $account->id;

        $vcard = new VCard([
            'TEL' => '01010101010',
        ]);

        $this->invokePrivateMethod($importVCard, 'importTel', [$contact, $vcard]);

        $this->assertDatabaseHas('contact_fields', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'data' => '01010101010',
        ]);
    }

    /** @test */
    public function it_imports_phone_by_national_format()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        factory(ContactFieldType::class)->create([
            'account_id' => $account->id,
            'type' => 'phone',
        ]);

        $importVCard = new ImportVCard;
        $importVCard->accountId = $account->id;

        $vcard = new VCard([
            'TEL' => '202-555-0191',
            'ADR' => ['', '', '17 Shakespeare Ave.', 'Southampton', '', 'SO17 2HB', 'United Kingdom'],
        ]);

        $this->invokePrivateMethod($importVCard, 'importTel', [$contact, $vcard]);

        $this->assertDatabaseHas('contact_fields', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'data' => '020 2555 0191',
        ]);
    }

    /** @test */
    public function it_imports_phone_by_international_format()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        factory(ContactFieldType::class)->create([
            'account_id' => $account->id,
            'type' => 'phone',
        ]);

        $vcard = new VCard([
            'TEL' => '+44(0)202-555-0191',
            'ADR' => ['', '', '17 Shakespeare Ave.', 'Southampton', '', 'SO17 2HB', 'United Kingdom'],
        ]);

        $importVCard = new ImportVCard;
        $importVCard->accountId = $account->id;

        $this->invokePrivateMethod($importVCard, 'importTel', [$contact, $vcard]);

        $this->assertDatabaseHas('contact_fields', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'data' => '+44 20 2555 0191',
        ]);
    }

    /** @test */
    public function it_imports_email_labels()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $email = factory(ContactField::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $importVCard = new ImportVCard;
        $importVCard->accountId = $account->id;

        $vcard = new VCard();
        $vcard->add(
            'EMAIL',
            'test@test.com',
            [
                'type' => ['WORK'],
            ]
        );

        $this->invokePrivateMethod($importVCard, 'importEmail', [$contact, $vcard]);

        $this->assertDatabaseHas('contact_fields', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'data' => 'test@test.com',
        ]);
        $this->assertDatabaseHas('contact_field_labels', [
            'account_id' => $account->id,
            'label_i18n' => 'work',
        ]);

        $contactFieldLabel = ContactFieldLabel::where([
            'account_id' => $account->id,
            'label_i18n' => 'work',
        ])->first();
        $this->assertDatabaseHas('contact_field_contact_field_label', [
            'account_id' => $account->id,
            'contact_field_id' => $email->id,
            'contact_field_label_id' => $contactFieldLabel->id,
        ]);
        $email->refresh();
        $this->assertEquals($email->data, 'test@test.com');
    }

    /** @test */
    public function it_imports_address_labels()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $email = factory(ContactField::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $importVCard = new ImportVCard;
        $importVCard->accountId = $account->id;

        $vcard = new VCard();
        $vcard->add(
            'ADR',
            ['', '', '5 Avenue Anatole France', 'Paris', '', '75007', 'France'],
            [
                'type' => ['HOME'],
            ]
        );

        $this->invokePrivateMethod($importVCard, 'importAddress', [$contact, $vcard]);

        $this->assertDatabaseHas('addresses', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);
        $this->assertDatabaseHas('contact_field_labels', [
            'account_id' => $account->id,
            'label_i18n' => 'home',
        ]);

        $address = Address::where([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ])->first();
        $contactFieldLabel = ContactFieldLabel::where([
            'account_id' => $account->id,
            'label_i18n' => 'home',
        ])->first();
        $this->assertDatabaseHas('address_contact_field_label', [
            'account_id' => $account->id,
            'address_id' => $address->id,
            'contact_field_label_id' => $contactFieldLabel->id,
        ]);
    }

    /** @test */
    public function it_imports_categories()
    {
        $account = factory(Account::class)->create();
        $importVCard = new ImportVCard;
        $importVCard->accountId = $account->id;

        $tag1 = factory(Tag::class)->create([
            'account_id' => $account->id,
            'name' => 'tag1',
            'name_slug' => Str::slug('tag1'),
        ]);
        $tag2 = factory(Tag::class)->create([
            'account_id' => $account->id,
            'name' => 'tag2',
            'name_slug' => Str::slug('tag2'),
        ]);

        $vcard = new VCard([
            'CATEGORIES' => ['tag1', 'tag2'],
        ]);

        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $this->invokePrivateMethod($importVCard, 'importCategories', [$contact, $vcard]);

        $this->assertDatabaseHas('contact_tag', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'tag_id' => $tag1->id,
        ]);
        $this->assertDatabaseHas('contact_tag', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'tag_id' => $tag2->id,
        ]);
    }

    /** @test */
    public function it_imports_notes()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);

        $importVCard = new ImportVCard;
        $importVCard->accountId = $account->id;

        $vcard = new VCard([
            'NOTE' => 'a great note about this contact',
        ]);

        $this->invokePrivateMethod($importVCard, 'importNote', [$contact, $vcard]);

        $this->assertDatabaseHas('notes', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'body' => 'a great note about this contact',
        ]);
    }

    /** @test */
    public function it_imports_new_categories()
    {
        $account = factory(Account::class)->create();
        $importVCard = new ImportVCard;
        $importVCard->accountId = $account->id;

        $tag1 = factory(Tag::class)->create([
            'account_id' => $account->id,
            'name' => 'tag1',
            'name_slug' => Str::slug('tag1'),
        ]);
        $tag2 = factory(Tag::class)->create([
            'account_id' => $account->id,
            'name' => 'tag2',
            'name_slug' => Str::slug('tag2'),
        ]);
        $tag3 = factory(Tag::class)->create([
            'account_id' => $account->id,
            'name' => 'tag3',
            'name_slug' => Str::slug('tag3'),
        ]);

        $vcard = new VCard([
            'CATEGORIES' => ['tag2', 'tag3'],
        ]);

        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $contact->tags()->sync([
            $tag1->id => ['account_id' => $contact->account_id],
            $tag2->id => ['account_id' => $contact->account_id],
        ]);
        $this->assertDatabaseHas('contact_tag', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'tag_id' => $tag1->id,
        ]);
        $this->assertDatabaseHas('contact_tag', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'tag_id' => $tag2->id,
        ]);

        $this->invokePrivateMethod($importVCard, 'importCategories', [$contact, $vcard]);

        $this->assertDatabaseMissing('contact_tag', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'tag_id' => $tag1->id,
        ]);
        $this->assertDatabaseHas('contact_tag', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'tag_id' => $tag2->id,
        ]);
        $this->assertDatabaseHas('contact_tag', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'tag_id' => $tag3->id,
        ]);
    }

    /** @test */
    public function it_imports_uuid_default()
    {
        $account = factory(Account::class)->create();
        $importVCard = new ImportVCard;
        $importVCard->accountId = $account->id;

        $vcard = new VCard([
            'UID' => '31fdc242-c974-436e-98de-6b21624d6e34',
        ]);

        $contact = [];

        $contact = $this->invokePrivateMethod($importVCard, 'importUid', [$contact, $vcard]);

        $this->assertEquals('31fdc242-c974-436e-98de-6b21624d6e34', $contact['uuid']);
    }

    /** @test */
    public function it_imports_uuid_contact()
    {
        $user = factory(User::class)->create([]);
        $importVCard = new ImportVCard;
        $importVCard->accountId = $user->account_id;
        $importVCard->userId = $user->id;

        $vcard = new VCard([
            'FN'   => 'John Doe',
            'UID' => '31fdc242-c974-436e-98de-6b21624d6e34',
        ]);

        $contact = $this->invokePrivateMethod($importVCard, 'importEntry', [null, $vcard, $vcard->serialize(), null]);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account_id,
            'id' => $contact->id,
            'uuid' => '31fdc242-c974-436e-98de-6b21624d6e34',
        ]);
        $this->assertEquals('31fdc242-c974-436e-98de-6b21624d6e34', $contact->uuid);
    }
}
