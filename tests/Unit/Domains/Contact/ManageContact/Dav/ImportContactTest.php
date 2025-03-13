<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Dav;

use App\Domains\Contact\Dav\Services\ImportVCard;
use App\Domains\Contact\ManageContact\Dav\ImportContact;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\PHPUnitAssertions;
use Tests\TestCase;

class ImportContactTest extends TestCase
{
    use DatabaseTransactions,
        PHPUnitAssertions;

    #[Group('dav')]
    #[Test]
    public function it_imports_names_n()
    {
        $importContact = new ImportContact;

        $vcard = new VCard([
            'N' => ['Doe', 'John', 'Jane', '', ''],
        ]);
        $contact = $importContact->importNames([], $vcard);

        $this->assertEquals('John', $contact['first_name']);
        $this->assertEquals('Doe', $contact['last_name']);
        $this->assertEquals('Jane', $contact['middle_name']);
    }

    #[Group('dav')]
    #[Test]
    public function it_imports_names_nickname()
    {
        $importContact = new ImportContact;

        $vcard = new VCard([
            'NICKNAME' => 'John',
        ]);
        $contact = $importContact->importNames([], $vcard);

        $this->assertEquals('John', $contact['first_name']);
    }

    #[Group('dav')]
    #[Test]
    public function it_imports_names_fn()
    {
        $author = User::factory()->create();
        $importVCard = new ImportVCard;
        $importVCard->author = $author;
        $importContact = new ImportContact;
        $importContact->setContext($importVCard);

        $vcard = new VCard([
            'FN' => 'John Doe',
        ]);
        $contact = $importContact->importNames([], $vcard);

        $this->assertEquals('John', $contact['first_name']);
        $this->assertEquals('Doe', $contact['last_name']);
    }

    #[Group('dav')]
    #[Test]
    public function it_imports_names_f_n_last()
    {
        $author = User::factory()->create([
            'name_order' => '%last_name% %first_name%',
        ]);
        $importVCard = new ImportVCard;
        $importVCard->author = $author;
        $importContact = new ImportContact;
        $importContact->setContext($importVCard);

        $vcard = new VCard([
            'FN' => 'John Doe',
        ]);
        $contact = $importContact->importNames([], $vcard);

        $this->assertEquals('Doe', $contact['first_name']);
        $this->assertEquals('John', $contact['last_name']);
    }

    #[Group('dav')]
    #[Test]
    public function it_imports_names_f_n_extra_space()
    {
        $author = User::factory()->create();
        $importVCard = new ImportVCard;
        $importVCard->author = $author;
        $importContact = new ImportContact;
        $importContact->setContext($importVCard);

        $vcard = new VCard([
            'FN' => 'John  Doe',
        ]);
        $contact = $importContact->importNames([], $vcard);

        $this->assertEquals('John', $contact['first_name']);
        $this->assertEquals('Doe', $contact['last_name']);
    }

    #[Group('dav')]
    #[Test]
    public function it_imports_name_fn()
    {
        $author = User::factory()->create();
        $importVCard = new ImportVCard;
        $importVCard->author = $author;
        $importContact = new ImportContact;
        $importContact->setContext($importVCard);

        $vcard = new VCard([
            'FN' => 'John',
            'N' => 'Mike;;;;',
        ]);
        $contact = $importContact->importNames([], $vcard);

        $this->assertEquals('John', $contact['first_name']);
        $this->assertEquals('', Arr::get($contact, 'last_name'));
    }

    #[Group('dav')]
    #[Test]
    public function it_imports_name_f_n_last()
    {
        $author = User::factory()->create([
            'name_order' => '%last_name% %first_name%',
        ]);
        $importVCard = new ImportVCard;
        $importVCard->author = $author;
        $importContact = new ImportContact;
        $importContact->setContext($importVCard);

        $vcard = new VCard([
            'FN' => 'John',
            'N' => 'Mike;;;;',
        ]);
        $contact = $importContact->importNames([], $vcard);

        $this->assertEquals('John', $contact['last_name']);
        $this->assertEquals('', Arr::get($contact, 'first_name'));
    }

    #[Group('dav')]
    #[Test]
    public function it_imports_names_f_n_multiple()
    {
        $author = User::factory()->create();
        $importVCard = new ImportVCard;
        $importVCard->author = $author;
        $importContact = new ImportContact;
        $importContact->setContext($importVCard);

        $vcard = new VCard([
            'FN' => 'John Doe Marco',
            'N' => 'Mike;;;;',
        ]);
        $contact = $importContact->importNames([], $vcard);

        $this->assertEquals('John', $contact['first_name']);
        $this->assertEquals('Doe Marco', $contact['last_name']);
    }

    #[Group('dav')]
    #[Test]
    public function it_imports_uuid_default()
    {
        $importContact = new ImportContact;
        $importContact->setContext(new ImportVCard($this->app));

        $vcard = new VCard([
            'UID' => '31fdc242-c974-436e-98de-6b21624d6e34',
        ]);

        $contact = [];

        $contact = $this->invokePrivateMethod($importContact, 'importUid', [$contact, $vcard]);

        $this->assertEquals('31fdc242-c974-436e-98de-6b21624d6e34', $contact['id']);
    }
}
