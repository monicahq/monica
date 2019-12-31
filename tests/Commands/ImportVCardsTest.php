<?php

namespace Tests\Commands;

use Mockery as m;
use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ImportVCardsTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_validates_user()
    {
        $this->withoutMockingConsoleOutput();

        $path = base_path('tests/stubs/vcard_stub.vcf');

        $command = m::mock('\App\Console\Commands\ImportVCards[error]', [new \Illuminate\Filesystem\Filesystem()]);

        $command->shouldReceive('error')->once()->with('No user with that email.');

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $exitCode = $this->artisan('import:vcard', ['--user' => 'notfound@example.com', '--path' => $path, '--no-interaction' => true]);

        $this->assertEquals(0, $exitCode);
    }

    /** @test */
    public function it_validates_file()
    {
        $this->withoutMockingConsoleOutput();

        $user = $this->getUser();

        $command = m::mock('\App\Console\Commands\ImportVCards[error]', [new \Illuminate\Filesystem\Filesystem()]);

        $command->shouldReceive('error')->once()->with('The provided vcard file was not found or is not valid!');

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $exitCode = $this->artisan('import:vcard', ['--user' => $user->email, '--path' => 'not_found', '--no-interaction' => true]);

        $this->assertEquals(0, $exitCode);
    }

    /** @test */
    public function it_imports_contacts()
    {
        $this->withoutMockingConsoleOutput();
        Storage::fake('public');

        $user = $this->getUser();
        $path = base_path('tests/stubs/vcard_stub.vcf');

        $totalContacts = Contact::where('account_id', $user->account_id)->count();

        $exitCode = $this->artisan('import:vcard', ['--user' => $user->email, '--path' => $path, '--no-interaction' => true]);

        $this->assertDatabaseHas('contacts', [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertDatabaseHas('contact_fields', [
            'data' => 'john.doe@example.com',
        ]);

        // Allows checking if birthday was correctly set
        $this->assertDatabaseHas('special_dates', [
            'date' => '1960-05-10',
        ]);

        // Allows checking nickname fallback
        $this->assertDatabaseHas('contacts', [
            'first_name' => 'Johnny',
        ]);

        $this->assertDatabaseHas('contacts', [
            'company' => 'U2',
            'job' => 'Lead vocalist',
        ]);

        // Allows checking addresses are correctly saved
        $this->assertDatabaseHas('places', [
            'street' => '17 Shakespeare Ave.',
            'postal_code' => 'SO17 2HB',
            'city' => 'Southampton',
            'country' => 'GB',
        ]);

        $this->assertDatabaseHas('contact_fields', [
            'data' => 'bono@example.com',
        ]);

        $this->assertDatabaseHas('contact_fields', [
            'data' => '+1 202-555-0191',
        ]);

        // Asserts that only 3 new contacts were created
        $this->assertEquals(
            $totalContacts + 3,
            Contact::where('account_id', $user->account_id)->count()
        );

        $this->assertEquals(0, $exitCode);
    }

    private function getUser()
    {
        $account = Account::createDefault('John', 'Doe', 'johndoe@example.com', 'secret');

        return $account->users()->first();
    }
}
