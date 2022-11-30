<?php

namespace Tests\Unit\Domains\Contact\DAV\Services;

use App\Domains\Contact\Dav\Services\ImportVCard;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\PHPUnitAssertions;
use Sabre\VObject\Reader;
use Tests\TestCase;

class ImportVCardTest extends TestCase
{
    use DatabaseTransactions,
        PHPUnitAssertions;

    /** @test */
    public function it_can_not_import_because_no_firstname_or_nickname_in_vcard()
    {
        $importVCard = new ImportVCard($this->app);

        $vcard = new VCard([]);

        $this->assertFalse($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_can_not_import_because_no_firstname_in_vcard()
    {
        $importVCard = new ImportVCard($this->app);

        $vcard = new VCard([
            'N' => ['John', '', '', '', ''],
        ]);

        $this->assertFalse($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_can_not_import_because_empty_firstname_in_vcard()
    {
        $importVCard = new ImportVCard($this->app);

        $vcard = new VCard([
            'N' => ';;;;',
        ]);

        $this->assertFalse($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_can_not_import_vcard()
    {
        $importVCard = new ImportVCard($this->app);

        $vcard = Reader::read('
BEGIN:VCARD
VERSION:3.0
N:;;;;
FN:
ORG:;
EMAIL;TYPE=home;TYPE=pref:mail@example.org
NOTE:
NICKNAME:
TITLE:
REV:20210900T000102Z
END:VCARD', Reader::OPTION_FORGIVING + Reader::OPTION_IGNORE_INVALID_LINES);

        $this->assertFalse($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_can_not_import_because_empty_nickname_in_vcard()
    {
        $importVCard = new ImportVCard($this->app);

        $vcard = new VCard([
            'NICKNAME' => '',
        ]);

        $this->assertFalse($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_can_not_import_because_empty_fullname_in_vcard()
    {
        $importVCard = new ImportVCard($this->app);

        $vcard = new VCard([
            'FN' => '',
        ]);

        $this->assertFalse($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_can_import_firstname()
    {
        $importVCard = new ImportVCard($this->app);

        $vcard = new VCard([
            'N' => ['', 'John', '', '', ''],
        ]);

        $this->assertTrue($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_can_import_nickname()
    {
        $importVCard = new ImportVCard($this->app);

        $vcard = new VCard([
            'NICKNAME' => 'John',
        ]);

        $this->assertTrue($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_can_import_fullname()
    {
        $importVCard = new ImportVCard($this->app);

        $vcard = new VCard([
            'FN' => 'John Doe',
        ]);

        $this->assertTrue($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_returns_an_unknown_name_if_no_name_is_in_entry()
    {
        $importVCard = new ImportVCard($this->app);

        $vcard = new VCard([
            'EMAIL' => 'john@',
        ]);

        $this->assertEquals(
            'john@',
            $this->invokePrivateMethod($importVCard, 'name', [$vcard])
        );
    }

    /** @test */
    public function it_returns_a_name_for_N()
    {
        $importVCard = new ImportVCard($this->app);

        $vcard = new VCard([
            'N' => ['John', 'Doe', '', '', ''],
            'EMAIL' => 'john@doe.com',
        ]);

        $this->assertEquals('John Doe', $this->invokePrivateMethod($importVCard, 'name', [$vcard]));
    }

    /** @test */
    public function it_returns_a_name_for_N_incomplete()
    {
        $importVCard = new ImportVCard($this->app);

        $vcard = new VCard([
            'N' => ['John', 'Doe'],
            'EMAIL' => 'john@doe.com',
        ]);

        $this->assertEquals('John Doe', $this->invokePrivateMethod($importVCard, 'name', [$vcard]));
    }

    /** @test */
    public function it_returns_a_name_for_NICKNAME()
    {
        $importVCard = new ImportVCard($this->app);

        $vcard = new VCard([
            'NICKNAME' => 'John',
            'EMAIL' => 'john@doe.com',
        ]);

        $this->assertEquals('john@doe.com', $this->invokePrivateMethod($importVCard, 'name', [$vcard]));
    }

    /** @test */
    public function it_returns_a_name_for_FN()
    {
        $importVCard = new ImportVCard($this->app);

        $vcard = new VCard([
            'FN' => 'John Doe',
            'EMAIL' => 'john@doe.com',
        ]);

        $this->assertEquals('John Doe', $this->invokePrivateMethod($importVCard, 'name', [$vcard]));
    }

    /** @test */
    public function it_formats_value()
    {
        $importVCard = new ImportVCard($this->app);

        $result = $this->invokePrivateMethod($importVCard, 'formatValue', ['']);
        $this->assertNull($result);

        $result = $this->invokePrivateMethod($importVCard, 'formatValue', ['This is a value']);
        $this->assertEquals(
            'This is a value',
            $result
        );
    }

    /** @test */
    public function it_creates_a_contact()
    {
        $author = User::factory()->create();
        $vault = $this->createVaultUser($author, Vault::PERMISSION_EDIT);
        $importVCard = new ImportVCard($this->app);
        $importVCard->accountId = $author->account_id;
        $this->setPrivateValue($importVCard, 'author', $author);
        $this->setPrivateValue($importVCard, 'vault', $vault);

        $vcard = new VCard([
            'N' => ['John', 'Doe', '', '', ''],
            'EMAIL' => 'john@doe.com',
        ]);

        $contact = $this->invokePrivateMethod($importVCard, 'importEntry', [null, $vcard, $vcard->serialize(), null]);

        $this->assertTrue($contact->exists);
    }

    /** @test */
    public function it_imports_uuid_contact()
    {
        $author = User::factory()->create();
        $vault = $this->createVaultUser($author, Vault::PERMISSION_EDIT);
        $importVCard = new ImportVCard($this->app);
        $importVCard->accountId = $author->account_id;
        $this->setPrivateValue($importVCard, 'author', $author);
        $this->setPrivateValue($importVCard, 'vault', $vault);

        $vcard = new VCard([
            'FN' => 'John Doe',
            'UID' => '31fdc242-c974-436e-98de-6b21624d6e34',
        ]);

        $contact = $this->invokePrivateMethod($importVCard, 'importEntry', [null, $vcard, $vcard->serialize(), null]);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'uuid' => '31fdc242-c974-436e-98de-6b21624d6e34',
        ]);
        $this->assertEquals('31fdc242-c974-436e-98de-6b21624d6e34', $contact->uuid);
    }
}
