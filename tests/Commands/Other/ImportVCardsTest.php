<?php

namespace Tests\Commands\Other;

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
        $path = base_path('tests/stubs/vcard_stub.vcf');

        $this->artisan('import:vcard', ['--user' => 'notfound@example.com', '--path' => $path, '--no-interaction' => true])
            ->assertFailed()
            ->expectsOutput('No user with that email.')
            ->run();
    }

    /** @test */
    public function it_validates_file()
    {
        $user = $this->getUser();

        $this->artisan('import:vcard', ['--user' => $user->email, '--path' => 'not_found', '--no-interaction' => true])
            ->assertFailed()
            ->expectsOutput('The provided vcard file was not found or is not valid!')
            ->run();
    }

    /** @test */
    public function it_imports_contacts()
    {
        Storage::fake('public');

        $user = $this->getUser();
        $path = base_path('tests/stubs/vcard_stub.vcf');

        $totalContacts = Contact::where('account_id', $user->account_id)->count();

        $this->artisan('import:vcard', ['--user' => $user->email, '--path' => $path, '--no-interaction' => true])
            ->assertSuccessful()
            ->run();

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
    }

    private function getUser()
    {
        $account = Account::createDefault('John', 'Doe', 'johndoe@example.com', 'secret', null, 'en');

        return $account->users()->first();
    }
}
