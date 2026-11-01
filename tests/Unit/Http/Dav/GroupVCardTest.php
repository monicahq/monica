<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Dav;

use App\Http\Dav\GroupVCard;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupVCardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_the_group_in_both_the_rfc_and_the_apple_form(): void
    {
        $group = Group::factory()->create(['name' => 'Halpert household']);
        $jim = $this->member($group, ['first_name' => 'Jim']);
        $pam = $this->member($group, ['first_name' => 'Pam']);

        $card = new GroupVCard($group)->serialize();

        $this->assertSame(
            "BEGIN:VCARD\r\n"
            ."VERSION:4.0\r\n"
            ."UID:{$group->uuid}\r\n"
            ."KIND:group\r\n"
            ."FN:Halpert household\r\n"
            ."MEMBER:urn:uuid:{$jim->uuid}\r\n"
            ."MEMBER:urn:uuid:{$pam->uuid}\r\n"
            ."X-ADDRESSBOOKSERVER-KIND:group\r\n"
            ."X-ADDRESSBOOKSERVER-MEMBER:urn:uuid:{$jim->uuid}\r\n"
            ."X-ADDRESSBOOKSERVER-MEMBER:urn:uuid:{$pam->uuid}\r\n"
            ."END:VCARD\r\n",
            $card,
        );
    }

    #[Test]
    public function it_names_only_the_current_members_the_address_book_holds(): void
    {
        $group = Group::factory()->create(['name' => 'Scranton branch']);
        $jim = $this->member($group, ['first_name' => 'Jim']);
        $devon = $this->member($group, ['first_name' => 'Devon', 'is_archived' => true]);
        $mose = $this->member($group, ['first_name' => 'Mose', 'is_partial' => true]);
        $roy = $this->member($group, ['first_name' => 'Roy']);
        $roy->delete();
        $ryan = Contact::factory()->create(['vault_id' => $group->vault_id, 'account_id' => $group->account_id]);
        GroupMember::factory()->former()->create(['group_id' => $group->id, 'contact_id' => $ryan->id]);

        $card = new GroupVCard($group)->serialize();

        $this->assertStringContainsString("MEMBER:urn:uuid:{$jim->uuid}", $card);
        $this->assertStringNotContainsString($devon->uuid, $card);
        $this->assertStringNotContainsString($mose->uuid, $card);
        $this->assertStringNotContainsString($roy->uuid, $card);
        $this->assertStringNotContainsString($ryan->uuid, $card);
    }

    #[Test]
    public function it_names_a_member_by_the_uid_its_own_card_carries(): void
    {
        $group = Group::factory()->create();
        $dwight = $this->member($group, [
            'first_name' => 'Dwight',
            'vcard' => "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:dwight-from-the-phone\r\nFN:Dwight Schrute\r\nEND:VCARD\r\n",
        ]);

        $card = new GroupVCard($group)->serialize();

        $this->assertSame('dwight-from-the-phone', GroupVCard::uidOf($dwight));
        $this->assertStringContainsString('MEMBER:urn:uuid:dwight-from-the-phone', $card);
        $this->assertStringNotContainsString($dwight->uuid, $card);
    }

    #[Test]
    public function it_falls_back_to_the_uuid_when_the_stored_card_has_no_uid(): void
    {
        $contact = Contact::factory()->create([
            'vcard' => "BEGIN:VCARD\r\nVERSION:4.0\r\nFN:Kevin Malone\r\nEND:VCARD\r\n",
        ]);

        $this->assertSame($contact->uuid, GroupVCard::uidOf($contact));
        $this->assertSame("urn:uuid:{$contact->uuid}", GroupVCard::referenceTo($contact));
    }

    /** @param  array<string, mixed>  $attributes */
    private function member(Group $group, array $attributes): Contact
    {
        $contact = Contact::factory()->create([
            ...$attributes,
            'vault_id' => $group->vault_id,
            'account_id' => $group->account_id,
        ]);

        GroupMember::factory()->create(['group_id' => $group->id, 'contact_id' => $contact->id]);

        return $contact;
    }
}
