<?php

namespace Tests\Unit;

use Tests\TestCase;
use Sabre\VObject\Component\VCard;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ImportJobTest extends TestCase
{
    use DatabaseTransactions;

    public $vcfContent = 'BEGIN:VCARD
VERSION:3.0
FN:Bono
N:;Bono;;;
EMAIL;TYPE=INTERNET:bono@example.com
TEL:+1 202-555-0191
ORG:U2
TITLE:Lead vocalist
BDAY:1960-05-10
NOTE:Lorem ipsum dolor sit amet
END:VCARD
BEGIN:VCARD
VERSION:3.0
FN:John Doe
N:Doe;John;;;
EMAIL;TYPE=INTERNET:john.doe@example.com
END:VCARD
BEGIN:VCARD
VERSION:3.0
FN:
N:;;;;
NICKNAME:Johnny
ADR:;;17 Shakespeare Ave.;Southampton;;SO17 2HB;United Kingdom
END:VCARD
    ';

    public function test_it_belongs_to_a_user()
    {
        $user = factory('App\User')->create([]);
        $importJob = factory('App\ImportJob')->create(['user_id' => $user->id]);

        $this->assertTrue($importJob->user()->exists());
    }

    public function test_it_belongs_to_an_account()
    {
        $account = factory('App\Account')->create([]);
        $importJob = factory('App\ImportJob')->create(['account_id' => $account->id]);

        $this->assertTrue($importJob->account()->exists());
    }

    public function test_it_belongs_to_many_reports()
    {
        $importJob = factory('App\ImportJob')->create([]);
        $importJobReport = factory('App\ImportJobReport', 100)->create(['import_job_id' => $importJob->id]);

        $this->assertTrue($importJob->importJobReports()->exists());
    }

    public function test_it_initiates_the_job()
    {
        $importJob = factory('App\ImportJob')->make([]);

        $this->assertNull($importJob->started_at);

        $importJob->initJob();

        $this->assertNotNull($importJob->started_at);
    }

    public function test_it_finalizes_the_job()
    {
        $importJob = factory('App\ImportJob')->make([]);

        $this->assertNull($importJob->ended_at);

        $importJob->endJob();

        $this->assertNotNull($importJob->ended_at);
    }

    public function test_it_creates_a_new_specific_gender()
    {
        $account = factory('App\Account')->create([]);
        $importJob = factory('App\ImportJob')->create(['account_id' => $account->id]);

        $existingNumberOfGenders = \App\Gender::all()->count();

        $importJob->getSpecialGender();

        $this->assertInstanceOf('App\Gender', $importJob->gender);

        $this->assertEquals(
            $existingNumberOfGenders + 1,
            \App\Gender::all()->count()
        );
    }

    public function test_it_gets_an_existing_gender()
    {
        $account = factory('App\Account')->create([]);
        $importJob = factory('App\ImportJob')->create(['account_id' => $account->id]);
        $gender = factory('App\Gender')->create([
            'account_id' => $account->id,
            'name' => 'vCard',
        ]);
        $existingNumberOfGenders = \App\Gender::all()->count();

        $importJob->getSpecialGender();

        $this->assertInstanceOf('App\Gender', $importJob->gender);

        $this->assertEquals(
            $existingNumberOfGenders,
            \App\Gender::all()->count()
        );
    }

    public function test_it_fails_and_throws_an_exception()
    {
        $importJob = factory('App\ImportJob')->create([]);
        $importJob->fail('reason');

        $this->assertTrue($importJob->failed);
        $this->assertEquals(
            'reason',
            $importJob->failed_reason
        );
    }

    public function test_it_gets_the_physical_file()
    {
        Storage::fake('public');
        $importJob = factory('App\ImportJob')->create([
            'filename' => 'testfile.vcf',
        ]);

        Storage::disk('public')->put(
            'testfile.vcf',
            'fakeContent'
        );

        Storage::disk('public')->assertExists($importJob->filename);

        $this->assertNull($importJob->physicalFile);
        $importJob->getPhysicalFile();

        $this->assertEquals(
            'fakeContent',
            $importJob->physicalFile
        );
    }

    public function test_it_throws_an_exception_if_file_doesnt_exist()
    {
        Storage::fake('public');
        $importJob = factory('App\ImportJob')->create([
            'filename' => 'testfile.vcf',
        ]);

        $importJob->getPhysicalFile();

        $this->assertEquals(
            trans('settings.import_vcard_file_not_found'),
            $importJob->failed_reason
        );
    }

    public function test_it_deletes_the_file()
    {
        Storage::fake('public');
        $importJob = factory('App\ImportJob')->create([
            'filename' => 'testfile.vcf',
        ]);

        Storage::disk('public')->put(
            'testfile.vcf',
            'fakeContent'
        );

        $importJob->deletePhysicalFile();

        Storage::disk('public')->assertMissing($importJob->filename);
    }

    public function test_it_throws_an_exception_if_file_cant_be_deleted()
    {
        Storage::fake('public');
        $importJob = factory('App\ImportJob')->create([
            'filename' => 'testfile.vcf',
        ]);

        $importJob->deletePhysicalFile();
        $this->assertEquals(
            trans('settings.import_vcard_file_not_found'),
            $importJob->failed_reason
        );
    }

    public function test_it_calculates_how_many_entries_there_are_and_populate_the_entries_array()
    {
        Storage::fake('public');
        $importJob = factory('App\ImportJob')->create([
            'filename' => 'testfile.vcf',
        ]);

        Storage::disk('public')->put(
            'testfile.vcf',
            $this->vcfContent
        );

        $importJob->getPhysicalFile();
        $importJob->getEntries();

        $this->assertEquals(
            3,
            $importJob->contacts_found
        );

        $this->assertEquals(
            3,
            count($importJob->entries[0])
        );
    }

    public function test_it_doesnt_process_an_entry_if_import_is_not_feasible()
    {
        $importJob = $this->createImportJob();

        $vcard = new VCard([
            'TEL' => '+1 555 34567 455',
            'N'   => ['', '', '', '', ''],
        ]);

        $importJob->currentEntry = $vcard;

        $importJob->processSingleEntry();
        $this->assertEquals(
            1,
            $importJob->contacts_skipped
        );
    }

    public function test_it_doesnt_process_an_entry_if_contact_already_exists()
    {
        $importJob = $this->createImportJob();
        $contact = factory('App\Contact')->create([
            'account_id' => $importJob->account->id,
        ]);
        $contactFieldType = factory('App\ContactFieldType')->create([
            'account_id' => $importJob->account->id,
            'type' => 'email',
        ]);
        $contactField = factory('App\ContactField')->create([
            'account_id' => $importJob->account->id,
            'contact_id' => $contact->id,
            'contact_field_type_id' => $contactFieldType->id,
            'data' => 'john@doe.com',
        ]);

        $vcard = new VCard([
            'N' => ['John', 'Doe', '', '', ''],
            'EMAIL' => 'john@doe.com',
        ]);

        $importJob->currentEntry = $vcard;

        $importJob->processSingleEntry();
        $this->assertEquals(
            1,
            $importJob->contacts_skipped
        );
    }

    public function test_skipping_entries_increments_counter_and_file_job_report()
    {
        $importJob = $this->createImportJob();
        $vcard = new VCard([
            'N' => ['John', 'Doe', '', '', ''],
            'EMAIL' => 'john@doe.com',
        ]);
        $importJob->currentEntry = $vcard;

        $importJob->skipEntry();

        $this->assertEquals(
            1,
            $importJob->contacts_skipped
        );

        $this->assertDatabaseHas('import_job_reports', [
            'account_id' => $importJob->account->id,
            'import_job_id' => $importJob->id,
        ]);
    }

    public function test_it_checks_import_feasibility()
    {
        $importJob = $this->createImportJob();

        // false because no N entry in the vcard
        $vcard = new VCard([]);
        $importJob->currentEntry = $vcard;
        $this->assertFalse($importJob->checkImportFeasibility());

        // false because no firstname
        $vcard = new VCard([
            'N'   => ['John', '', '', '', ''],
        ]);
        $importJob->currentEntry = $vcard;
        $this->assertFalse($importJob->checkImportFeasibility());

        // false because no nickname
        $vcard = new VCard([
            'NICKNAME'   => '',
            'N'   => '',
        ]);
        $importJob->currentEntry = $vcard;

        // false because no firstname
        $this->assertFalse($importJob->checkImportFeasibility());
    }

    public function test_it_validates_email()
    {
        $importJob = $this->createImportJob();

        $invalidEmail = 'test@';

        $this->assertFalse($importJob->isValidEmail($invalidEmail));

        $validEmail = 'john@doe.com';

        $this->assertTrue($importJob->isValidEmail($validEmail));
    }

    public function test_it_checks_if_a_contact_exists()
    {
        $importJob = $this->createImportJob();
        $contact = factory('App\Contact')->create([
            'account_id' => $importJob->account->id,
        ]);
        $contactFieldType = factory('App\ContactFieldType')->create([
            'account_id' => $importJob->account->id,
            'type' => 'email',
        ]);
        $contactField = factory('App\ContactField')->create([
            'account_id' => $importJob->account->id,
            'contact_id' => $contact->id,
            'contact_field_type_id' => $contactFieldType->id,
            'data' => 'john@doe.com',
        ]);

        $vcard = new VCard([
            'N' => ['John', 'Doe', '', '', ''],
            'EMAIL' => 'john@',
        ]);

        $importJob->currentEntry = $vcard;
        $contact = $importJob->existingContact();
        $this->assertNull($contact);
    }

    public function test_it_returns_an_unknown_name_if_no_name_is_in_entry()
    {
        $importJob = $this->createImportJob();
        $vcard = new VCard([
            'EMAIL' => 'john@',
        ]);

        $importJob->currentEntry = $vcard;

        $this->assertEquals(
            trans('settings.import_vcard_unknown_entry'),
            $importJob->name()
        );
    }

    public function test_it_returns_a_name_of_the_current_entry()
    {
        $importJob = $this->createImportJob();
        $vcard = new VCard([
            'N' => ['John', 'Doe', '', '', ''],
            'EMAIL' => 'john@doe.com',
        ]);

        $importJob->currentEntry = $vcard;

        $this->assertEquals(
            'Doe  John john@doe.com',
            $importJob->name()
        );
    }

    public function test_it_files_an_import_job_report()
    {
        $importJob = $this->createImportJob();
        $vcard = new VCard([
            'N' => ['John', 'Doe', '', '', ''],
            'EMAIL' => 'john@doe.com',
        ]);

        $importJob->currentEntry = $vcard;

        $importJob->fileImportJobReport($importJob::VCARD_SKIPPED);
        $this->assertDatabaseHas('import_job_reports', [
            'account_id' => $importJob->account_id,
            'user_id' => $importJob->user_id,
            'import_job_id' => $importJob->id,
            'contact_information' => 'Doe  John john@doe.com',
            'skipped' => 1,
            'skip_reason' => null,
        ]);

        $importJob->fileImportJobReport($importJob::VCARD_IMPORTED);
        $this->assertDatabaseHas('import_job_reports', [
            'account_id' => $importJob->account_id,
            'user_id' => $importJob->user_id,
            'import_job_id' => $importJob->id,
            'contact_information' => 'Doe  John john@doe.com',
            'skipped' => 0,
            'skip_reason' => null,
        ]);

        $importJob->fileImportJobReport($importJob::VCARD_SKIPPED, 'the reason why');
        $this->assertDatabaseHas('import_job_reports', [
            'account_id' => $importJob->account_id,
            'user_id' => $importJob->user_id,
            'import_job_id' => $importJob->id,
            'contact_information' => 'Doe  John john@doe.com',
            'skipped' => 1,
            'skip_reason' => 'the reason why',
        ]);
    }

    public function test_it_formats_value()
    {
        $importJob = new \App\ImportJob;

        $result = $this->invokePrivateMethod($importJob, 'formatValue', ['']);
        $this->assertNull($result);

        $result = $this->invokePrivateMethod($importJob, 'formatValue', ['This is a value']);
        $this->assertEquals(
            'This is a value',
            $result
        );
    }

    public function test_it_creates_a_contact()
    {
        $importJob = $this->createImportJob();
        $vcard = new VCard([
            'N' => ['John', 'Doe', '', '', ''],
            'EMAIL' => 'john@doe.com',
        ]);

        $importJob->currentEntry = $vcard;

        // we need the gender - otherwise the contact can't be created
        // as it requires the gender 'vCard'
        $importJob->getSpecialGender();
        $numberOfContacts = \App\Contact::all()->count();
        $numberOfFiledJobReport = \App\ImportJobReport::all()->count();

        $importJob->createContactFromCurrentEntry();

        // have we increased the counter
        $this->assertEquals(
            1,
            $importJob->contacts_imported
        );

        // have we actually added a new contact in the database
        $newNumberOfContacts = \App\Contact::all()->count();
        $this->assertEquals(
            $numberOfContacts + 1,
            $newNumberOfContacts
        );

        // have we actually created a new import job report
        $newNumberOfFiledJobReport = \App\ImportJobReport::all()->count();
        $this->assertEquals(
            $numberOfFiledJobReport + 1,
            $newNumberOfFiledJobReport
        );
    }

    public function test_it_imports_names()
    {
        $importJob = $this->createImportJob();
        $vcard = new VCard([
            'N' => ['John', 'Doe', '', '', ''],
        ]);

        $importJob->currentEntry = $vcard;
        $contact = new \App\Contact;
        $importJob->importNames($contact);

        $this->assertEquals(
            'Doe',
            $contact->first_name
        );
        $this->assertEquals(
            'John',
            $contact->last_name
        );

        $vcard = new VCard([
            'NICKNAME' => 'John',
        ]);

        $importJob->currentEntry = $vcard;
        $contact = new \App\Contact;
        $importJob->importNames($contact);

        $this->assertEquals(
            'John',
            $contact->first_name
        );
    }

    public function test_it_imports_work_information()
    {
        $importJob = $this->createImportJob();
        $vcard = new VCard([
            'ORG' => 'Company',
            'ROLE' => 'Branleur',
        ]);

        $importJob->currentEntry = $vcard;
        $contact = new \App\Contact;
        $importJob->importWorkInformation($contact);

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
        $importJob = $this->createImportJob();
        $vcard = new VCard([
            'BDAY' => '1990-01-01',
        ]);

        $importJob->currentEntry = $vcard;
        $contact = factory('App\Contact')->create([
            'account_id' => $importJob->account->id,
        ]);
        $importJob->importBirthday($contact);

        $this->assertNotNull($contact->birthday_special_date_id);
    }

    public function test_it_imports_address()
    {
        $importJob = $this->createImportJob();
        $vcard = new VCard([
            'ADR' => ['data', 'data', 'data', 'data', 'data', 'data', 'us'],
        ]);

        $importJob->currentEntry = $vcard;
        $contact = factory('App\Contact')->create([
            'account_id' => $importJob->account->id,
        ]);
        $importJob->importAddress($contact);

        $this->assertDatabaseHas('addresses', [
            'account_id' => $importJob->account_id,
            'contact_id' => $contact->id,
        ]);
    }

    public function test_it_imports_email()
    {
        $importJob = $this->createImportJob();
        $vcard = new VCard([
            'EMAIL' => 'john@doe.com',
        ]);

        $importJob->currentEntry = $vcard;
        $contact = factory('App\Contact')->create([
            'account_id' => $importJob->account->id,
        ]);
        $importJob->importEmail($contact);

        $this->assertDatabaseHas('contact_fields', [
            'account_id' => $importJob->account_id,
            'contact_id' => $contact->id,
            'data' => 'john@doe.com',
        ]);
    }

    public function test_it_imports_phone()
    {
        $importJob = $this->createImportJob();
        $vcard = new VCard([
            'TEL' => '01010101010',
        ]);

        $importJob->currentEntry = $vcard;
        $contact = factory('App\Contact')->create([
            'account_id' => $importJob->account->id,
        ]);
        $importJob->importTel($contact);

        $this->assertDatabaseHas('contact_fields', [
            'account_id' => $importJob->account_id,
            'contact_id' => $contact->id,
            'data' => '01010101010',
        ]);
    }

    private function createImportJob()
    {
        $account = factory('App\Account')->create([]);
        $user = factory('App\User')->create([
            'account_id' => $account->id,
        ]);
        $importJob = factory('App\ImportJob')->create([
            'account_id' => $account->id,
            'user_id' => $user->id,
        ]);

        return $importJob;
    }
}
