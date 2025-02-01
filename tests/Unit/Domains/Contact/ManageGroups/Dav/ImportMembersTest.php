<?php

namespace Tests\Unit\Domains\Contact\ManageGroups\Dav;

use App\Domains\Contact\Dav\Services\ImportVCard;
use App\Domains\Contact\ManageGroups\Dav\ImportMembers;
use App\Models\Contact;
use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\PHPUnitAssertions;
use Tests\TestCase;

class ImportMembersTest extends TestCase
{
    use DatabaseTransactions,
        PHPUnitAssertions;

    /** @test */
    public function it_imports_a_member()
    {
        $importGroup = new ImportMembers;
        $importGroup->setContext(new ImportVCard($this->app));

        $vcard = new VCard([
            'MEMBER' => '31fdc242-c974-436e-98de-6b21624d6e34',
        ]);

        $members = $this->invokePrivateMethod($importGroup, 'importMembers', [$vcard]);

        $this->assertContains('31fdc242-c974-436e-98de-6b21624d6e34', $members);
    }

    /** @test */
    public function it_imports_multiple_members()
    {
        $importGroup = new ImportMembers;
        $importGroup->setContext(new ImportVCard($this->app));

        $vcard = new VCard;
        $vcard->add('MEMBER', '31fdc242-c974-436e-98de-6b21624d6e34');
        $vcard->add('MEMBER', '61fdc242-c974-436e-98de-6b21624d6e34');

        $data = [];

        $members = $this->invokePrivateMethod($importGroup, 'importMembers', [$vcard]);

        $this->assertContains('31fdc242-c974-436e-98de-6b21624d6e34', $members);
        $this->assertContains('61fdc242-c974-436e-98de-6b21624d6e34', $members);
    }

    /** @test */
    public function it_updates_members()
    {
        $vault = $this->createVaultUser($user = User::factory()->create(), Vault::PERMISSION_MANAGE);
        $importGroup = new ImportMembers;
        $importGroup->setContext(tap(new ImportVCard($this->app), function ($importVCard) use ($user, $vault) {
            $importVCard->author = $user;
            $importVCard->vault = $vault;
        }));

        $group = Group::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $members = collect([
            $contact->id,
        ]);

        $this->invokePrivateMethod($importGroup, 'updateGroupMembers', [$group, $members]);

        $group = $group->refresh();
        $c = $group->contacts->first();

        $this->assertEquals($contact->id, $c->id);
    }

    /** @test */
    public function it_keeps_existing_members()
    {
        $vault = $this->createVaultUser($user = User::factory()->create(), Vault::PERMISSION_MANAGE);
        $importGroup = new ImportMembers;
        $importGroup->setContext(tap(new ImportVCard($this->app), function ($importVCard) use ($user, $vault) {
            $importVCard->author = $user;
            $importVCard->vault = $vault;
        }));

        $group = Group::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $group->contacts()->syncWithoutDetaching([
            $contact->id => ['group_type_role_id' => null],
        ]);

        $members = collect([
            $contact->id,
        ]);

        $this->invokePrivateMethod($importGroup, 'updateGroupMembers', [$group, $members]);

        $group = $group->refresh();
        $c = $group->contacts->first();

        $this->assertEquals($contact->id, $c->id);
    }

    /** @test */
    public function it_keeps_existing_members_and_add_new()
    {
        $vault = $this->createVaultUser($user = User::factory()->create(), Vault::PERMISSION_MANAGE);
        $importGroup = new ImportMembers;
        $importGroup->setContext(tap(new ImportVCard($this->app), function ($importVCard) use ($user, $vault) {
            $importVCard->author = $user;
            $importVCard->vault = $vault;
        }));

        $group = Group::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $contact1 = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $group->contacts()->syncWithoutDetaching([
            $contact1->id => ['group_type_role_id' => null],
        ]);
        $contact2 = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $members = [
            $contact1->id,
            $contact2->id,
        ];

        $this->invokePrivateMethod($importGroup, 'updateGroupMembers', [$group, collect($members)]);

        $group = $group->refresh();
        collect($group->contacts->all())->each(fn ($contact) => $this->assertContains($contact->id, $members)
        );
    }

    /** @test */
    public function it_removes_old_members()
    {
        $vault = $this->createVaultUser($user = User::factory()->create(), Vault::PERMISSION_MANAGE);
        $importGroup = new ImportMembers;
        $importGroup->setContext(tap(new ImportVCard($this->app), function ($importVCard) use ($user, $vault) {
            $importVCard->author = $user;
            $importVCard->vault = $vault;
        }));

        $group = Group::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $contact1 = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $group->contacts()->syncWithoutDetaching([
            $contact1->id => ['group_type_role_id' => null],
        ]);
        $contact2 = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $group->contacts()->syncWithoutDetaching([
            $contact2->id => ['group_type_role_id' => null],
        ]);

        $members = [
            $contact2->id,
        ];

        $this->invokePrivateMethod($importGroup, 'updateGroupMembers', [$group, collect($members)]);

        $group = $group->refresh();
        collect($group->contacts->all())->each(fn ($contact) => $this->assertContains($contact->id, $members)
        );
    }
}
