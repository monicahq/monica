<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Account\ImportJob;
use Sabre\VObject\Component\VCard;
use App\Models\Contact\ContactField;
use App\Models\Account\ImportJobReport;
use Illuminate\Support\Facades\Storage;
use App\Models\Contact\ContactFieldType;
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

    /** @test */
    public function it_belongs_to_a_user()
    {
        $importJob = factory(ImportJob::class)->create();

        $this->assertTrue($importJob->user()->exists());
    }

    /** @test */
    public function it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);
        $importJob = factory(ImportJob::class)->create([
            'account_id' => $account->id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue($importJob->account()->exists());
    }

    /** @test */
    public function it_belongs_to_many_reports()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);
        $importJob = factory(ImportJob::class)->create([
            'account_id' => $account->id,
            'user_id' => $user->id,
        ]);
        factory(ImportJobReport::class, 100)->create([
            'import_job_id' => $importJob->id,
            'account_id' => $account->id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue($importJob->importJobReports()->exists());
    }

    /** @test */
    public function it_initiates_the_job()
    {
        $importJob = factory(ImportJob::class)->make([]);

        $this->assertNull($importJob->started_at);

        $this->invokePrivateMethod($importJob, 'initJob');

        $this->assertNotNull($importJob->started_at);
    }

    /** @test */
    public function it_finalizes_the_job()
    {
        $importJob = factory(ImportJob::class)->make([]);

        $this->assertNull($importJob->ended_at);

        $this->invokePrivateMethod($importJob, 'endJob');

        $this->assertNotNull($importJob->ended_at);
    }

    /** @test */
    public function it_fails_and_throws_an_exception()
    {
        $importJob = factory(ImportJob::class)->create([]);
        $this->invokePrivateMethod($importJob, 'fail', [
            'reason',
        ]);

        $this->assertTrue($importJob->failed);
        $this->assertEquals(
            'reason',
            $importJob->failed_reason
        );
    }

    /** @test */
    public function it_gets_the_physical_file()
    {
        Storage::fake('public');
        $importJob = factory(ImportJob::class)->create([
            'filename' => 'testfile.vcf',
        ]);

        Storage::disk('public')->put(
            'testfile.vcf',
            'fakeContent'
        );

        Storage::disk('public')->assertExists($importJob->filename);

        $this->assertNull($importJob->physicalFile);
        $this->invokePrivateMethod($importJob, 'getPhysicalFile');

        $this->assertEquals(
            'fakeContent',
            $importJob->physicalFile
        );
    }

    /** @test */
    public function it_throws_an_exception_if_file_doesnt_exist()
    {
        Storage::fake('public');
        $importJob = factory(ImportJob::class)->create([
            'filename' => 'testfile.vcf',
        ]);

        $this->invokePrivateMethod($importJob, 'getPhysicalFile');

        $this->assertEquals(
            trans('settings.import_vcard_file_not_found'),
            $importJob->failed_reason
        );
    }

    /** @test */
    public function it_deletes_the_file()
    {
        Storage::fake('public');
        $importJob = factory(ImportJob::class)->create([
            'filename' => 'testfile.vcf',
        ]);

        Storage::disk('public')->put(
            'testfile.vcf',
            'fakeContent'
        );

        $this->invokePrivateMethod($importJob, 'deletePhysicalFile');

        Storage::disk('public')->assertMissing($importJob->filename);
    }

    /** @test */
    public function it_throws_an_exception_if_file_cant_be_deleted()
    {
        Storage::fake('public');
        $importJob = factory(ImportJob::class)->create([
            'filename' => 'testfile.vcf',
        ]);

        $this->invokePrivateMethod($importJob, 'deletePhysicalFile');
        $this->assertEquals(
            trans('settings.import_vcard_file_not_found'),
            $importJob->failed_reason
        );
    }

    /** @test */
    public function it_calculates_how_many_entries_there_are_and_populate_the_entries_array()
    {
        Storage::fake('public');
        $importJob = $this->createImportJob();
        $importJob->filename = 'testfile.vcf';

        Storage::disk('public')->put(
            'testfile.vcf',
            $this->vcfContent
        );

        $this->invokePrivateMethod($importJob, 'getPhysicalFile');
        $this->invokePrivateMethod($importJob, 'getEntries');
        $this->invokePrivateMethod($importJob, 'processEntries');
        $this->assertJobSuccess($importJob);

        $this->assertEquals(
            3,
            $importJob->contacts_found
        );
    }

    /** @test */
    public function it_doesnt_process_an_entry_if_import_is_not_feasible()
    {
        $importJob = $this->createImportJob();

        $vcard = new VCard([
            'TEL' => '+1 555 34567 455',
            'N'   => ['', '', '', '', ''],
        ]);
        $this->invokePrivateMethod($importJob, 'processSingleEntry', [
            $vcard->serialize(),
        ]);
        $this->assertJobSuccess($importJob);
        $this->assertEquals(
            1,
            $importJob->contacts_skipped
        );
    }

    /** @test */
    public function it_doesnt_process_an_entry_if_contact_already_exists()
    {
        $importJob = $this->createImportJob();
        $contact = factory(Contact::class)->create([
            'account_id' => $importJob->account_id,
        ]);
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $importJob->account_id,
            'type' => 'email',
        ]);
        $contactField = factory(ContactField::class)->create([
            'account_id' => $importJob->account_id,
            'contact_id' => $contact->id,
            'contact_field_type_id' => $contactFieldType->id,
            'data' => 'john@doe.com',
        ]);

        $vcard = new VCard([
            'N' => ['John', 'Doe', '', '', ''],
            'EMAIL' => 'john@doe.com',
        ]);

        $this->invokePrivateMethod($importJob, 'processSingleEntry', [$vcard]);
        $this->assertJobSuccess($importJob);
        $this->assertEquals(
            1,
            $importJob->contacts_skipped
        );
    }

    /** @test */
    public function skipping_entries_increments_counter_and_file_job_report()
    {
        $importJob = $this->createImportJob();

        $this->invokePrivateMethod($importJob, 'skipEntry', [
            'John Doe',
        ]);

        $this->assertEquals(
            1,
            $importJob->contacts_skipped
        );

        $this->assertDatabaseHas('import_job_reports', [
            'account_id' => $importJob->account_id,
            'import_job_id' => $importJob->id,
        ]);
    }

    /** @test */
    public function it_files_an_import_job_report()
    {
        $importJob = $this->createImportJob();
        $vcard = new VCard([
            'N' => ['John', 'Doe', '', '', ''],
            'EMAIL' => 'john@doe.com',
        ]);

        $this->invokePrivateMethod($importJob, 'fileImportJobReport', [
            'Doe  John john@doe.com',
            $importJob::VCARD_SKIPPED,
        ]);
        $this->assertDatabaseHas('import_job_reports', [
            'account_id' => $importJob->account_id,
            'user_id' => $importJob->user_id,
            'import_job_id' => $importJob->id,
            'contact_information' => 'Doe  John john@doe.com',
            'skipped' => 1,
            'skip_reason' => null,
        ]);

        $this->invokePrivateMethod($importJob, 'fileImportJobReport', [
            'Doe  John john@doe.com',
            $importJob::VCARD_IMPORTED,
        ]);
        $this->assertDatabaseHas('import_job_reports', [
            'account_id' => $importJob->account_id,
            'user_id' => $importJob->user_id,
            'import_job_id' => $importJob->id,
            'contact_information' => 'Doe  John john@doe.com',
            'skipped' => 0,
            'skip_reason' => null,
        ]);

        $this->invokePrivateMethod($importJob, 'fileImportJobReport', [
            'Doe  John john@doe.com',
            $importJob::VCARD_SKIPPED,
            'the reason why',
        ]);
        $this->assertDatabaseHas('import_job_reports', [
            'account_id' => $importJob->account_id,
            'user_id' => $importJob->user_id,
            'import_job_id' => $importJob->id,
            'contact_information' => 'Doe  John john@doe.com',
            'skipped' => 1,
            'skip_reason' => 'the reason why',
        ]);
    }

    private function createImportJob()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);

        return factory(ImportJob::class)->create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'failed' => false,
        ]);
    }

    private function assertJobSuccess($importJob)
    {
        $this->assertFalse($importJob->failed, 'Job has failed, reason: '.$importJob->failed_reason);
    }
}
