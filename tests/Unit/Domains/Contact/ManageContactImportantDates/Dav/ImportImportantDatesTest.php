<?php

namespace Tests\Unit\Domains\Contact\ManageContactImportantDates\Dav;

use App\Domains\Contact\Dav\Services\ImportVCard;
use App\Domains\Contact\ManageContactImportantDates\Dav\ImportImportantDates;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use App\Models\ContactImportantDateType;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\PHPUnitAssertions;
use Tests\TestCase;

class ImportImportantDatesTest extends TestCase
{
    use DatabaseTransactions,
        PHPUnitAssertions;

    #[Group('dav')]
    #[Test]
    public function it_imports_bday()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        $importVCard = new ImportVCard;
        $importVCard->author = $user;
        $importVCard->vault = $vault;
        $importer = new ImportImportantDates;
        $importer->setContext($importVCard);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        ContactImportantDateType::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $vcard = new VCard([
            'BDAY' => '20251106',
        ]);

        $contact = $importer->import($vcard, $contact);

        $this->assertCount(1, $contact->importantDates);
        $importantDate = $contact->importantDates->first();
        $this->assertEquals(6, $importantDate->day);
        $this->assertEquals(11, $importantDate->month);
        $this->assertEquals(2025, $importantDate->year);
    }

    #[Group('dav')]
    #[Test]
    public function it_imports_bday_part()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        $importVCard = new ImportVCard;
        $importVCard->author = $user;
        $importVCard->vault = $vault;
        $importer = new ImportImportantDates;
        $importer->setContext($importVCard);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        ContactImportantDateType::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $vcard = new VCard([
            'BDAY' => '2025----',
        ]);

        $contact = $importer->import($vcard, $contact);

        $this->assertCount(1, $contact->importantDates);
        $importantDate = $contact->importantDates->first();
        $this->assertNull($importantDate->day);
        $this->assertNull($importantDate->month);
        $this->assertEquals(2025, $importantDate->year);
    }

    #[Group('dav')]
    #[Test]
    public function it_imports_bday_and_remove_old_bday()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        $importVCard = new ImportVCard;
        $importVCard->author = $user;
        $importVCard->vault = $vault;
        $importer = new ImportImportantDates;
        $importer->setContext($importVCard);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        ContactImportantDateType::factory()->create([
            'vault_id' => $vault->id,
        ]);

        ContactImportantDate::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $vcard = new VCard([
            'BDAY' => '20251106',
        ]);

        $contact = $importer->import($vcard, $contact);

        $this->assertCount(1, $contact->importantDates);
        $importantDate = $contact->importantDates->first();
        $this->assertEquals(6, $importantDate->day);
        $this->assertEquals(11, $importantDate->month);
        $this->assertEquals(2025, $importantDate->year);
    }

    #[Group('dav')]
    #[Test]
    public function it_imports_bdays_no_change()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        $importVCard = new ImportVCard;
        $importVCard->author = $user;
        $importVCard->vault = $vault;
        $importer = new ImportImportantDates;
        $importer->setContext($importVCard);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        ContactImportantDateType::factory()->create([
            'vault_id' => $vault->id,
        ]);

        ContactImportantDate::factory()->create([
            'contact_id' => $contact->id,
        ]);
        $vcard = new VCard([
            'BDAY' => '19811029',
        ]);

        $contact = $importer->import($vcard, $contact);

        $this->assertCount(1, $contact->importantDates);
        $importantDate = $contact->importantDates->first();
        $this->assertEquals(29, $importantDate->day);
        $this->assertEquals(10, $importantDate->month);
        $this->assertEquals(1981, $importantDate->year);
    }
}
