<?php

namespace Tests\Unit\Services\VCard;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Sabre\VObject\Component\VCard;
use App\Services\VCard\ImportVCard;
use App\Models\Contact\ContactField;
use Sabre\VObject\PHPUnitAssertions;
use App\Models\Contact\ContactFieldType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ImportVCardTest extends TestCase
{
    use DatabaseTransactions,
        PHPUnitAssertions;

    public function test_it_can_not_import_because_no_firstname_or_nickname_in_vcard()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

        $vcard = new VCard([]);

        $this->assertFalse($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    public function test_it_can_not_import_because_no_firstname_in_vcard()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

        $vcard = new VCard([
            'N'   => ['John', '', '', '', ''],
        ]);

        $this->assertFalse($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    public function test_it_can_not_import_because_empty_nickname_in_vcard()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

        $vcard = new VCard([
            'NICKNAME'   => '',
        ]);

        $this->assertFalse($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    public function test_it_can_not_import_because_empty_fullname_in_vcard()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

        $vcard = new VCard([
            'FN'   => '',
        ]);

        $this->assertFalse($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    public function test_it_can_import_firstname()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

        $vcard = new VCard([
            'N'   => ['', 'John', '', '', ''],
        ]);

        $this->assertTrue($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    public function test_it_can_import_nickname()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

        $vcard = new VCard([
            'NICKNAME'   => 'John',
        ]);

        $this->assertTrue($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    public function test_it_can_import_fullname()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

        $vcard = new VCard([
            'FN'   => 'John Doe',
        ]);

        $this->assertTrue($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    public function test_it_validates_email()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

        $invalidEmail = 'test@';

        $this->assertFalse($this->invokePrivateMethod($importVCard, 'isValidEmail', [$invalidEmail]));

        $validEmail = 'john@doe.com';

        $this->assertTrue($this->invokePrivateMethod($importVCard, 'isValidEmail', [$validEmail]));
    }

    public function test_it_checks_if_a_contact_exists()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);
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

    public function test_it_returns_an_unknown_name_if_no_name_is_in_entry()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

        $vcard = new VCard([
            'EMAIL' => 'john@',
        ]);

        $this->assertEquals(
            trans('settings.import_vcard_unknown_entry'),
            $this->invokePrivateMethod($importVCard, 'name', [$vcard])
        );
    }

    public function test_it_returns_a_name_for_N()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

        $vcard = new VCard([
            'N' => ['John', 'Doe', '', '', ''],
            'EMAIL' => 'john@doe.com',
        ]);

        $this->assertEquals('Doe  John john@doe.com', $this->invokePrivateMethod($importVCard, 'name', [$vcard]));
    }

    public function test_it_returns_a_name_for_NICKNAME()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

        $vcard = new VCard([
            'NICKNAME' => 'John',
            'EMAIL' => 'john@doe.com',
        ]);

        $this->assertEquals('John john@doe.com', $this->invokePrivateMethod($importVCard, 'name', [$vcard]));
    }

    public function test_it_returns_a_name_for_FN()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

        $vcard = new VCard([
            'FN' => 'John Doe',
            'EMAIL' => 'john@doe.com',
        ]);

        $this->assertEquals('John Doe john@doe.com', $this->invokePrivateMethod($importVCard, 'name', [$vcard]));
    }

    public function test_it_formats_value()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

        $result = $this->invokePrivateMethod($importVCard, 'formatValue', ['']);
        $this->assertNull($result);

        $result = $this->invokePrivateMethod($importVCard, 'formatValue', ['This is a value']);
        $this->assertEquals(
            'This is a value',
            $result
        );
    }

    public function test_it_creates_a_contact()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

        $vcard = new VCard([
            'N' => ['John', 'Doe', '', '', ''],
            'EMAIL' => 'john@doe.com',
        ]);
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $account->id,
            'type' => 'email',
        ]);

        $contact = $this->invokePrivateMethod($importVCard, 'importEntry', [null, $vcard]);

        $this->assertTrue($contact->exists);
    }

    public function test_it_imports_names_N()
    {
        $contact = new Contact;

        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

        $vcard = new VCard([
            'N' => ['Doe', 'John', 'Jane', '', ''],
        ]);
        $this->invokePrivateMethod($importVCard, 'importNames', [$contact, $vcard]);

        $this->assertEquals('John', $contact->first_name);
        $this->assertEquals('Doe', $contact->last_name);
        $this->assertEquals('Jane', $contact->middle_name);
    }

    public function test_it_imports_names_NICKNAME()
    {
        $contact = new Contact;

        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

        $vcard = new VCard([
            'NICKNAME' => 'John',
        ]);
        $this->invokePrivateMethod($importVCard, 'importNames', [$contact, $vcard]);

        $this->assertEquals('John', $contact->first_name);
    }

    public function test_it_imports_names_FN()
    {
        $contact = new Contact;

        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

        $vcard = new VCard([
            'FN' => 'John Doe',
        ]);
        $this->invokePrivateMethod($importVCard, 'importNames', [$contact, $vcard]);

        $this->assertEquals('John', $contact->first_name);
        $this->assertEquals('Doe', $contact->last_name);
    }

    public function test_it_imports_names_FN_extra_space()
    {
        $contact = new Contact;

        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

        $vcard = new VCard([
            'FN' => 'John  Doe',
        ]);
        $this->invokePrivateMethod($importVCard, 'importNames', [$contact, $vcard]);

        $this->assertEquals('John', $contact->first_name);
        $this->assertEquals('Doe', $contact->last_name);
    }

    public function test_it_imports_work_information()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

        $vcard = new VCard([
            'ORG' => 'Company',
            'ROLE' => 'Branleur',
        ]);

        $contact = new Contact;
        $this->invokePrivateMethod($importVCard, 'importWorkInformation', [$contact, $vcard]);

        $this->assertEquals(
            'Company',
            $contact->company
        );

        $this->assertEquals(
            'Branleur',
            $contact->job
        );
    }

    public function test_it_imports_birthday()
    {
        config(['monica.requires_subscription' => false]);

        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

        $vcard = new VCard([
            'BDAY' => '1990-01-01',
        ]);

        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $this->invokePrivateMethod($importVCard, 'importBirthday', [$contact, $vcard]);

        $this->assertNotNull($contact->birthday_special_date_id);
    }

    public function test_it_imports_address()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

        $vcard = new VCard([
            'ADR' => ['data', 'data', 'data', 'data', 'data', 'data', 'us'],
        ]);

        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $this->invokePrivateMethod($importVCard, 'importAddress', [$contact, $vcard]);

        $this->assertDatabaseHas('addresses', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);
    }

    public function test_it_imports_email()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

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

    public function test_it_imports_phone()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

        $vcard = new VCard([
            'TEL' => '01010101010',
        ]);

        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $account->id,
            'type' => 'phone',
        ]);
        $this->invokePrivateMethod($importVCard, 'importTel', [$contact, $vcard]);

        $this->assertDatabaseHas('contact_fields', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'data' => '01010101010',
        ]);
    }

    public function test_it_imports_phone_by_international_format()
    {
        $account = factory(Account::class)->create([]);
        $importVCard = new ImportVCard($account->id);

        $vcard = new VCard([
            'TEL' => '202-555-0191',
            'ADR' => ['', '', '17 Shakespeare Ave.', 'Southampton', '', 'SO17 2HB', 'United Kingdom'],
        ]);

        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $account->id,
            'type' => 'phone',
        ]);
        $this->invokePrivateMethod($importVCard, 'importTel', [$contact, $vcard]);

        $this->assertDatabaseHas('contact_fields', [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'data' => '+44 20 2555 0191',
        ]);
    }
}
