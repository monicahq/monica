<?php

namespace Tests\Commands;

use Mockery as m;
use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ImportCSVTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function csv_import_contacts()
    {
        $this->withoutMockingConsoleOutput();
        Storage::fake('public');

        $user = $this->getUser();
        $path = base_path('tests/stubs/single_contact_stub.csv');

        $totalContacts = Contact::where('account_id', $user->account_id)->count();

        $exitCode = $this->artisan('import:csv '.$user->email.' '.$path);

        $this->assertDatabaseHas('contacts', [
            'first_name' => 'Bono',
            'last_name' => 'Hewson',
        ]);

        $this->assertDatabaseHas('contact_fields', [
            'data' => 'bono@example.com',
        ]);

        // Allows checking if birthday was correctly set
        $this->assertDatabaseHas('special_dates', [
            'date' => '1960-05-10',
        ]);

        // Asserts that only 3 new contacts were created
        $this->assertEquals(
            $totalContacts + 1,
            Contact::where('account_id', $user->account_id)->count()
        );

        $this->assertEquals(0, $exitCode);
    }

    /** @test */
    public function csv_import_validates_user()
    {
        $this->withoutMockingConsoleOutput();

        $path = base_path('tests/stubs/single_contact_stub.csv');

        $command = m::mock('\App\Console\Commands\ImportCSV[error]', [new \Illuminate\Filesystem\Filesystem()]);

        $command->shouldReceive('error')->once()->with('You need to provide a valid User ID or email address!');

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $exitCode = $this->artisan('import:csv test@test.com '.$path);

        $this->assertEquals(-1, $exitCode);
    }

    /** @test */
    public function csv_import_validates_file()
    {
        $this->withoutMockingConsoleOutput();

        $user = $this->getUser();

        $command = m::mock('\App\Console\Commands\ImportCSV[error]', [new \Illuminate\Filesystem\Filesystem()]);

        $command->shouldReceive('error')->once()->with('You need to provide a valid file path.');

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $exitCode = $this->artisan('import:csv '.$user->email.' xxx');

        $this->assertEquals(-1, $exitCode);
    }

    private function getUser()
    {
        $account = Account::createDefault('John', 'Doe', 'johndoe@example.com', 'secret');

        return $account->users()->first();
    }
}
