<?php

namespace Tests\Unit\Domains\Contact\ManageGroups\Dav;

use App\Domains\Contact\ManageGroups\Dav\ExportMembers;
use App\Models\Contact;
use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\PHPUnitAssertions;
use Tests\TestCase;

class ExportMembersTest extends TestCase
{
    use DatabaseTransactions,
        PHPUnitAssertions;

    /** @test */
    public function it_exports_a_member()
    {
        $vault = $this->createVaultUser(User::factory()->create(), Vault::PERMISSION_MANAGE);
        $group = Group::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $group->contacts()->syncWithoutDetaching([
            $contact->id => ['group_type_role_id' => null],
        ]);

        $exportMembers = new ExportMembers;

        $vcard = new VCard([
            'KIND' => 'group',
        ]);

        $exportMembers->export($group, $vcard);

        $members = collect($vcard->select('MEMBER'))->map(fn ($member): string => (string) $member);

        $this->assertCount(1, $members);
        $this->assertContains($contact->id, $members);
    }

    /** @test */
    public function it_exports_removing_members()
    {
        $vault = $this->createVaultUser(User::factory()->create(), Vault::PERMISSION_MANAGE);
        $group = Group::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $group->contacts()->syncWithoutDetaching([
            $contact->id => ['group_type_role_id' => null],
        ]);

        $exportMembers = new ExportMembers;

        $vcard = new VCard([
            'KIND' => 'group',
            'MEMBER' => 'false',
        ]);

        $exportMembers->export($group, $vcard);

        $members = collect($vcard->select('MEMBER'))->map(fn ($member): string => (string) $member);

        $this->assertCount(1, $members);
        $this->assertContains($contact->id, $members);
        $this->assertNotContains('false', $members);
    }

    /** @test */
    public function it_exports_keeping_members()
    {
        $vault = $this->createVaultUser(User::factory()->create(), Vault::PERMISSION_MANAGE);
        $group = Group::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $group->contacts()->syncWithoutDetaching([
            $contact->id => ['group_type_role_id' => null],
        ]);

        $exportMembers = new ExportMembers;

        $vcard = new VCard([
            'KIND' => 'group',
            'MEMBER' => $contact->id,
        ]);

        $exportMembers->export($group, $vcard);

        $members = collect($vcard->select('MEMBER'))->map(fn ($member): string => (string) $member);

        $this->assertCount(1, $members);
        $this->assertContains($contact->id, $members);
    }
}
