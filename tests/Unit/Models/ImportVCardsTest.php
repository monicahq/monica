<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



namespace Tests\Unit\Models;

use Mockery as m;
use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ImportVCardsTest extends TestCase
{
    use DatabaseTransactions;

    public function testItValidatesUser()
    {
        $this->withoutMockingConsoleOutput();

        $path = base_path('tests/stubs/vcard_stub.vcf');

        $command = m::mock('\App\Console\Commands\ImportVCards[error]', [new \Illuminate\Filesystem\Filesystem()]);

        $command->shouldReceive('error')->once()->with('You need to provide a valid user email!');

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $exitCode = $this->artisan('import:vcard', ['user' => 'notfound@example.com', 'path' => $path, '--no-interaction' => true]);

        $this->assertEquals(0, $exitCode);
    }

    public function testItValidatesFile()
    {
        $this->withoutMockingConsoleOutput();

        $user = $this->getUser();

        $command = m::mock('\App\Console\Commands\ImportVCards[error]', [new \Illuminate\Filesystem\Filesystem()]);

        $command->shouldReceive('error')->once()->with('The provided vcard file was not found or is not valid!');

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $exitCode = $this->artisan('import:vcard', ['user' => $user->email, 'path' => 'not_found', '--no-interaction' => true]);

        $this->assertEquals(0, $exitCode);
    }

    public function testItImportsContacts()
    {
        $this->withoutMockingConsoleOutput();

        $user = $this->getUser();
        $path = base_path('tests/stubs/vcard_stub.vcf');

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

        $this->assertDatabaseHas('contacts', [
            'company' => 'U2',
            'job' => 'Lead vocalist',
        ]);

        // Allows checking addresses are correctly saved
        $this->assertDatabaseHas('addresses', [
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
