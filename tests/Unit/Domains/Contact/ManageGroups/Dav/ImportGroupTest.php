<?php

namespace Tests\Unit\Domains\Contact\ManageGroups\Dav;

use App\Domains\Contact\Dav\Services\ImportVCard;
use App\Domains\Contact\ManageGroups\Dav\ImportGroup;
use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\PHPUnitAssertions;
use Tests\TestCase;

class ImportGroupTest extends TestCase
{
    use DatabaseTransactions,
        PHPUnitAssertions;

    /** @test */
    public function it_imports_names_n()
    {
        $importGroup = new ImportGroup;

        $vcard = new VCard([
            'N' => ['NameGroup', '', '', '', ''],
        ]);
        $group = $importGroup->importNames([], $vcard);

        $this->assertEquals('NameGroup', $group['name']);
    }

    /** @test */
    public function it_imports_names_fn()
    {
        $author = User::factory()->create();
        $importVCard = new ImportVCard;
        $importVCard->author = $author;
        $importGroup = new ImportGroup;
        $importGroup->setContext($importVCard);

        $vcard = new VCard([
            'FN' => 'Name Group',
        ]);
        $group = $importGroup->importNames([], $vcard);

        $this->assertEquals('Name Group', $group['name']);
    }

    /** @test */
    public function it_imports_name_fn()
    {
        $author = User::factory()->create();
        $importVCard = new ImportVCard;
        $importVCard->author = $author;
        $importGroup = new ImportGroup;
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
        $importGroup = new ImportGroup;
        $importGroup->setContext(new ImportVCard($this->app));

        $vcard = new VCard([
            'UID' => '31fdc242-c974-436e-98de-6b21624d6e34',
        ]);

        $group = [];

        $group = $this->invokePrivateMethod($importGroup, 'importUid', [$group, $vcard]);

        $this->assertEquals('31fdc242-c974-436e-98de-6b21624d6e34', $group['id']);
    }

    /** @test */
    public function it_updates_name()
    {
        $vault = $this->createVaultUser($user = User::factory()->create(), Vault::PERMISSION_MANAGE);
        $importGroup = new ImportGroup;
        $importGroup->setContext(tap(new ImportVCard($this->app), function ($importVCard) use ($user, $vault) {
            $importVCard->author = $user;
            $importVCard->vault = $vault;
        }));

        $group = Group::factory()->create([
            'vault_id' => $vault->id,
            'distant_uuid' => '31fdc242-c974-436e-98de-6b21624d6e34',
        ]);

        $vcard = new VCard([
            'UID' => '31fdc242-c974-436e-98de-6b21624d6e34',
            'KIND' => 'group',
            'FN' => 'New Name',
        ]);

        $importGroup->import($vcard, $group);

        $group->refresh();

        $this->assertEquals('New Name', $group->name);
    }
}
