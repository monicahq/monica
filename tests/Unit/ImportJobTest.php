<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ImportJobTest extends TestCase
{
    use DatabaseTransactions;

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
}
