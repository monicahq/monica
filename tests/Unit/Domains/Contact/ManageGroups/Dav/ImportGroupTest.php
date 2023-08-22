<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Dav;

use App\Domains\Contact\Dav\Services\ImportVCard;
use App\Domains\Contact\ManageGroups\Dav\ImportGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\PHPUnitAssertions;
use Tests\TestCase;

class ImportGroupTest extends TestCase
{
    use DatabaseTransactions,
        PHPUnitAssertions;

    /** @test */
    public function it_imports_names_N()
    {
        $importGroup = new ImportGroup();

        $vcard = new VCard([
            'N' => ['NameGroup', '', '', '', ''],
        ]);
        $group = $importGroup->importNames([], $vcard);

        $this->assertEquals('NameGroup', $group['name']);
    }

    /** @test */
    public function it_imports_names_FN()
    {
        $author = User::factory()->create();
        $importVCard = new ImportVCard($this->app);
        $importVCard->author = $author;
        $importGroup = new ImportGroup();
        $importGroup->setContext($importVCard);

        $vcard = new VCard([
            'FN' => 'Name Group',
        ]);
        $group = $importGroup->importNames([], $vcard);

        $this->assertEquals('Name Group', $group['name']);
    }

    /** @test */
    public function it_imports_name_FN()
    {
        $author = User::factory()->create();
        $importVCard = new ImportVCard($this->app);
        $importVCard->author = $author;
        $importGroup = new ImportGroup();
        $importGroup->setContext($importVCard);

        $vcard = new VCard([
            'FN' => 'Other Name',
            'N' => 'Name Group',
        ]);
        $group = $importGroup->importNames([], $vcard);

        $this->assertEquals('Name Group', $group['name']);
    }

    /** @test */
    public function it_imports_uuid_default()
    {
        $importGroup = new ImportGroup();
        $importGroup->setContext(new ImportVCard($this->app));

        $vcard = new VCard([
            'UID' => '31fdc242-c974-436e-98de-6b21624d6e34',
        ]);

        $group = [];

        $group = $this->invokePrivateMethod($importGroup, 'importUid', [$group, $vcard]);

        $this->assertEquals('31fdc242-c974-436e-98de-6b21624d6e34', $group['id']);
    }
}
