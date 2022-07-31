<?php

namespace Tests\Commands\Other;

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
        Storage::fake('public');

        $user = $this->getUser();
        $path = base_path('tests/stubs/single_contact_stub.csv');

        $totalContacts = Contact::where('account_id', $user->account_id)->count();

        $this->artisan('import:csv', [
            'user' => $user->email,
            'file' => $path,
        ])
            ->assertSuccessful()
            ->run();

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
    }

    /** @test */
    public function csv_import_validates_user()
    {
        $path = base_path('tests/stubs/single_contact_stub.csv');

        $this->artisan('import:csv', [
            'user' => 'test@test.com',
            'file' => $path,
        ])
            ->assertFailed()
            ->expectsOutput('You need to provide a valid User ID or email address!')
            ->run();
    }

    /** @test */
    public function csv_import_validates_file()
    {
        $user = $this->getUser();

        $this->artisan('import:csv', [
            'user' => $user->email,
            'file' => 'xxx',
        ])
            ->assertFailed()
            ->expectsOutput('You need to provide a valid file path.')
            ->run();
    }

    private function getUser()
    {
        $account = Account::createDefault('John', 'Doe', 'johndoe@example.com', 'secret', null, 'en');

        return $account->users()->first();
    }
}
