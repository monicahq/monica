<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Dav;

use App\Domains\Contact\Dav\Services\ImportVCard;
use App\Domains\Contact\ManageContact\Dav\ImportAddress;
use App\Models\Address;
use App\Models\AddressType;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\PHPUnitAssertions;
use Tests\TestCase;

class ImportAddressTest extends TestCase
{
    use DatabaseTransactions,
        PHPUnitAssertions;

    #[Group('dav')]
    #[Test]
    public function it_imports_new_address()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        $importVCard = new ImportVCard;
        $importVCard->author = $user;
        $importVCard->vault = $vault;
        $importer = new ImportAddress;
        $importer->setContext($importVCard);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $vcard = new VCard([
            'ADR' => [
                '',
                'line 1',
                'line 2',
                'city',
                'province',
                'postal code',
                'country',
            ],
        ]);

        $contact = $importer->import($vcard, $contact);

        $this->assertCount(1, $contact->addresses);
        $address = $contact->addresses->first();
        $this->assertEquals('line 1', $address->line_1);
        $this->assertEquals('line 2', $address->line_2);
        $this->assertEquals('city', $address->city);
        $this->assertEquals('province', $address->province);
        $this->assertEquals('postal code', $address->postal_code);
        $this->assertEquals('country', $address->country);
    }

    #[Group('dav')]
    #[Test]
    public function it_imports_address_type()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        $importVCard = new ImportVCard;
        $importVCard->author = $user;
        $importVCard->vault = $vault;
        $importer = new ImportAddress;
        $importer->setContext($importVCard);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);

        AddressType::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'home',
            'type' => 'home',
        ]);

        $vcard = new VCard;
        $vcard->add('ADR', [
            '',
            'line 1',
            'line 2',
            'city',
            'province',
            'postal code',
            'country',
        ], [
            'TYPE' => 'home',
        ]);

        $contact = $importer->import($vcard, $contact);

        $this->assertCount(1, $contact->addresses);
        $address = $contact->addresses->first();

        $this->assertNotNull($address->addressType);
        $this->assertEquals('home', $address->addressType->name);
    }

    #[Group('dav')]
    #[Test]
    public function it_imports_new_address_type()
    {
        $user = $this->createAdministrator();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        $importVCard = new ImportVCard;
        $importVCard->author = $user;
        $importVCard->vault = $vault;
        $importer = new ImportAddress;
        $importer->setContext($importVCard);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $vcard = new VCard;
        $vcard->add('ADR', [
            '',
            'line 1',
            'line 2',
            'city',
            'province',
            'postal code',
            'country',
        ], [
            'TYPE' => 'home',
        ]);

        $contact = $importer->import($vcard, $contact);

        $this->assertCount(1, $contact->addresses);
        $address = $contact->addresses->first();

        $this->assertDatabaseHas('address_types', [
            'account_id' => $user->account_id,
            'name' => 'home',
        ]);

        $this->assertNotNull($address->addressType);
        $this->assertEquals('home', $address->addressType->name);
    }

    #[Group('dav')]
    #[Test]
    public function it_imports_new_address_and_remove_old()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        $importVCard = new ImportVCard;
        $importVCard->author = $user;
        $importVCard->vault = $vault;
        $importer = new ImportAddress;
        $importer->setContext($importVCard);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $addresses = Address::factory(2)->create([
            'vault_id' => $vault->id,
        ]);
        foreach ($addresses as $address) {
            $contact->addresses()->attach($address,
                ['is_past_address' => false]
            );
        }

        $vcard = new VCard([
            'ADR' => [
                '',
                'line 1',
                'line 2',
                'city',
                'province',
                'postal code',
                'country',
            ],
        ]);

        $contact = $importer->import($vcard, $contact);

        $this->assertCount(1, $contact->addresses);
        $address = $contact->addresses->first();
        $this->assertEquals('line 1', $address->line_1);
        $this->assertEquals('line 2', $address->line_2);
        $this->assertEquals('city', $address->city);
        $this->assertEquals('province', $address->province);
        $this->assertEquals('postal code', $address->postal_code);
        $this->assertEquals('country', $address->country);
    }
}
