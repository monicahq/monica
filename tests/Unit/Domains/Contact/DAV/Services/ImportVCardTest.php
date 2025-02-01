<?php

namespace Tests\Unit\Domains\Contact\DAV\Services;

use App\Domains\Contact\Dav\Services\ImportVCard;
use App\Models\Contact;
use App\Models\Group;
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
        $importVCard = new ImportVCard;

        $vcard = new VCard([]);

        $this->assertFalse($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_can_not_import_because_no_firstname_in_vcard()
    {
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'N' => ['John', '', '', '', ''],
        ]);

        $this->assertFalse($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_can_not_import_because_empty_firstname_in_vcard()
    {
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'N' => ';;;;',
        ]);

        $this->assertFalse($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_can_not_import_vcard()
    {
        $importVCard = new ImportVCard;

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
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'NICKNAME' => '',
        ]);

        $this->assertFalse($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_can_not_import_because_empty_fullname_in_vcard()
    {
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'FN' => '',
        ]);

        $this->assertFalse($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_can_import_firstname()
    {
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'N' => ['', 'John', '', '', ''],
        ]);

        $this->assertTrue($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_can_import_nickname()
    {
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'NICKNAME' => 'John',
        ]);

        $this->assertTrue($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_can_import_fullname()
    {
        $importVCard = new ImportVCard;

        $vcard = new VCard([
            'FN' => 'John Doe',
        ]);

        $this->assertTrue($this->invokePrivateMethod($importVCard, 'canImportCurrentEntry', [$vcard]));
    }

    /** @test */
    public function it_formats_value()
    {
        $importVCard = new ImportVCard;

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
        $importVCard = new ImportVCard;
        $importVCard->author = $author;
        $importVCard->vault = $vault;

        $vcard = new VCard([
            'N' => ['John', 'Doe', '', '', ''],
            'EMAIL' => 'john@doe.com',
        ]);

        $contact = $this->invokePrivateMethod($importVCard, 'importEntry', [$vcard]);

        $this->assertTrue($contact->exists);
    }

    /** @test */
    public function it_creates_a_contact_full()
    {
        $author = User::factory()->create();
        $vault = $this->createVaultUser($author, Vault::PERMISSION_EDIT);

        $vcard = 'BEGIN:VCARD
VERSION:3.0
UID:31fdc242-c974-436e-98de-6b21624d6e34
N:John;Doe;;;
FN:John Doe
REV:20210900T000102Z
END:VCARD';

        (new ImportVCard)->execute([
            'account_id' => $author->account_id,
            'author_id' => $author->id,
            'vault_id' => $vault->id,
            'entry' => $vcard,
            'behaviour' => 'behaviour_add',
            'external' => true,
        ]);

        $this->assertDatabaseHas('contacts', [
            'distant_uuid' => '31fdc242-c974-436e-98de-6b21624d6e34',
            'vault_id' => $vault->id,
            'first_name' => 'Doe',
            'last_name' => 'John',
        ]);
    }

    /** @test */
    public function it_creates_a_group_full()
    {
        $author = User::factory()->create();
        $vault = $this->createVaultUser($author, Vault::PERMISSION_EDIT);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $vcard = "BEGIN:VCARD
VERSION:3.0
UID:61fdc242-c974-436e-98de-6b21624d6e35
KIND:group
N:Colleagues;;;;
FN:Colleagues
MEMBER:{$contact->id}
REV:20210900T000102Z
END:VCARD";

        (new ImportVCard)->execute([
            'account_id' => $author->account_id,
            'author_id' => $author->id,
            'vault_id' => $vault->id,
            'entry' => $vcard,
            'behaviour' => 'behaviour_add',
            'external' => true,
        ]);

        $this->assertDatabaseHas('groups', [
            'distant_uuid' => '61fdc242-c974-436e-98de-6b21624d6e35',
            'vault_id' => $vault->id,
            'name' => 'Colleagues',
        ]);

        $group = Group::firstWhere('distant_uuid', '61fdc242-c974-436e-98de-6b21624d6e35');
        $this->assertDatabaseHas('contact_group', [
            'group_id' => $group->id,
            'contact_id' => $contact->id,
        ]);
    }

    /** @test */
    public function it_imports_uuid_contact()
    {
        $author = User::factory()->create();
        $vault = $this->createVaultUser($author, Vault::PERMISSION_EDIT);
        $importVCard = new ImportVCard;
        $importVCard->author = $author;
        $importVCard->vault = $vault;

        $vcard = new VCard([
            'FN' => 'John Doe',
            'UID' => '31fdc242-c974-436e-98de-6b21624d6e34',
        ]);

        $contact = $this->invokePrivateMethod($importVCard, 'importEntry', [$vcard]);

        $this->assertDatabaseHas('contacts', [
            'id' => '31fdc242-c974-436e-98de-6b21624d6e34',
        ]);
        $this->assertEquals('31fdc242-c974-436e-98de-6b21624d6e34', $contact->id);
    }
}
