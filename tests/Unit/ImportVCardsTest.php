<?php

namespace Tests\Unit;

use App\User;
use App\Account;
use App\Contact;
use App\Country;
use Mockery as m;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ImportVCardsTest extends TestCase
{
    use DatabaseTransactions;

    public function testItValidatesUser()
    {
        $path = 'tests/stubs/vcard_stub.vcf';

        $command = m::mock('\App\Console\Commands\ImportVCards[error]', [new \Illuminate\Filesystem\Filesystem()]);

        $command->shouldReceive('error')->once()->with('You need to provide a valid user email!');

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $exitCode = $this->artisan('import:vcard', ['user' => 'notfound@example.com', 'path' => $path, '--no-interaction' => true]);

        $this->assertEquals(0, $exitCode);
    }

    public function testItValidatesFile()
    {
        $user = $this->getUser();

        $command = m::mock('\App\Console\Commands\ImportVCards[error]', [new \Illuminate\Filesystem\Filesystem()]);

        $command->shouldReceive('error')->once()->with('The provided vcard file was not found or is not valid!');

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $exitCode = $this->artisan('import:vcard', ['user' => $user->email, 'path' => 'not_found', '--no-interaction' => true]);

        $this->assertEquals(0, $exitCode);
    }

    public function testItImportsContacts()
    {
        $user = $this->getUser();
        $path = 'tests/stubs/vcard_stub.vcf';

        $totalContacts = Contact::where('account_id', $user->account_id)->count();

        $exitCode = $this->artisan('import:vcard', ['user' => $user->email, 'path' => $path, '--no-interaction' => true]);

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

        // Allows checking addresses are correctly saved
        $this->assertDatabaseHas('addresses', [
            'street' => '17 Shakespeare Ave.',
            'postal_code' => 'SO17 2HB',
            'city' => 'Southampton',
            'country_id' => Country::where('country', 'United Kingdom')->first()->id,
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
        $user = new User();
        $user->first_name = 'John';
        $user->last_name = 'Doe';
        $user->email = 'johndoe@example.com';
        $user->password = bcrypt('secret');

        $account = new Account();
        $account->api_key = str_random(30);
        $account->save();

        $account->populateContactFieldTypeTable();
        $account->populateDefaultGendersTable();

        $user->account_id = $account->id;
        $user->save();

        return $user;
    }
}
