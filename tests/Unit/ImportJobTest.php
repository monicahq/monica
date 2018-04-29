<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Filesystem\FileNotFoundException;

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

    public $vcfContentWithoutName = 'BEGIN:VCARD
VERSION:3.0
FN:Bono
N:;;;;
EMAIL;TYPE=INTERNET:bono@example.com
TEL:+1 202-555-0191
ORG:U2
TITLE:Lead vocalist
BDAY:1960-05-10
NOTE:Lorem ipsum dolor sit amet
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

    /**
    * @expectedException Illuminate\Contracts\Filesystem\FileNotFoundException
    */
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

    public function test_it_calculates_how_many_entries_there_are()
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
        $importJob = factory('App\ImportJob')->create([]);

    }
}
