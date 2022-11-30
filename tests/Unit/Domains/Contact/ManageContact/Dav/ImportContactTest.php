<?php

namespace Tests\Unit\Domains\Contact\DAV\Services;

use App\Domains\Contact\Dav\Services\ImportVCard;
use App\Domains\Contact\ManageContact\Dav\ImportContact;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\PHPUnitAssertions;
use Tests\TestCase;

class ImportContactTest extends TestCase
{
    use DatabaseTransactions,
        PHPUnitAssertions;

    /** @test */
    public function it_imports_names_N()
    {
        $importContact = new ImportContact();

        $vcard = new VCard([
            'N' => ['Doe', 'John', 'Jane', '', ''],
        ]);
        $contact = $importContact->importNames([], $vcard);

        $this->assertEquals('John', $contact['first_name']);
        $this->assertEquals('Doe', $contact['last_name']);
        $this->assertEquals('Jane', $contact['middle_name']);
    }

    /** @test */
    public function it_imports_names_NICKNAME()
    {
        $importContact = new ImportContact();

        $vcard = new VCard([
            'NICKNAME' => 'John',
        ]);
        $contact = $importContact->importNames([], $vcard);

        $this->assertEquals('John', $contact['first_name']);
    }

    /** @test */
    public function it_imports_names_FN()
    {
        $author = User::factory()->create();
        $importVCard = new ImportVCard($this->app);
        $this->setPrivateValue($importVCard, 'author', $author);
        $importContact = new ImportContact();
        $importContact->setContext($importVCard);

        $vcard = new VCard([
            'FN' => 'John Doe',
        ]);
        $contact = $importContact->importNames([], $vcard);

        $this->assertEquals('John', $contact['first_name']);
        $this->assertEquals('Doe', $contact['last_name']);
    }

    /** @test */
    public function it_imports_names_FN_last()
    {
        $author = User::factory()->create([
            'name_order' => '%last_name% %first_name%',
        ]);
        $importVCard = new ImportVCard($this->app);
        $this->setPrivateValue($importVCard, 'author', $author);
        $importContact = new ImportContact();
        $importContact->setContext($importVCard);

        $vcard = new VCard([
            'FN' => 'John Doe',
        ]);
        $contact = $importContact->importNames([], $vcard);

        $this->assertEquals('Doe', $contact['first_name']);
        $this->assertEquals('John', $contact['last_name']);
    }

    /** @test */
    public function it_imports_names_FN_extra_space()
    {
        $author = User::factory()->create();
        $importVCard = new ImportVCard($this->app);
        $this->setPrivateValue($importVCard, 'author', $author);
        $importContact = new ImportContact();
        $importContact->setContext($importVCard);

        $vcard = new VCard([
            'FN' => 'John  Doe',
        ]);
        $contact = $importContact->importNames([], $vcard);

        $this->assertEquals('John', $contact['first_name']);
        $this->assertEquals('Doe', $contact['last_name']);
    }

    /** @test */
    public function it_imports_name_FN()
    {
        $author = User::factory()->create();
        $importVCard = new ImportVCard($this->app);
        $this->setPrivateValue($importVCard, 'author', $author);
        $importContact = new ImportContact();
        $importContact->setContext($importVCard);

        $vcard = new VCard([
            'FN' => 'John',
            'N' => 'Mike;;;;',
        ]);
        $contact = $importContact->importNames([], $vcard);

        $this->assertEquals('John', $contact['first_name']);
        $this->assertEquals('', Arr::get($contact, 'last_name'));
    }

    /** @test */
    public function it_imports_name_FN_last()
    {
        $author = User::factory()->create([
            'name_order' => '%last_name% %first_name%',
        ]);
        $importVCard = new ImportVCard($this->app);
        $this->setPrivateValue($importVCard, 'author', $author);
        $importContact = new ImportContact();
        $importContact->setContext($importVCard);

        $vcard = new VCard([
            'FN' => 'John',
            'N' => 'Mike;;;;',
        ]);
        $contact = $importContact->importNames([], $vcard);

        $this->assertEquals('John', $contact['last_name']);
        $this->assertEquals('', Arr::get($contact, 'first_name'));
    }

    /** @test */
    public function it_imports_names_FN_multiple()
    {
        $author = User::factory()->create();
        $importVCard = new ImportVCard($this->app);
        $this->setPrivateValue($importVCard, 'author', $author);
        $importContact = new ImportContact();
        $importContact->setContext($importVCard);

        $vcard = new VCard([
            'FN' => 'John Doe Marco',
            'N' => 'Mike;;;;',
        ]);
        $contact = $importContact->importNames([], $vcard);

        $this->assertEquals('John', $contact['first_name']);
        $this->assertEquals('Doe Marco', $contact['last_name']);
    }

    /** @test */
    public function it_imports_uuid_default()
    {
        $importContact = new ImportContact();

        $vcard = new VCard([
            'UID' => '31fdc242-c974-436e-98de-6b21624d6e34',
        ]);

        $contact = [];

        $contact = $this->invokePrivateMethod($importContact, 'importUid', [$contact, $vcard]);

        $this->assertEquals('31fdc242-c974-436e-98de-6b21624d6e34', $contact['uuid']);
    }
}
