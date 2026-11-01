<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Dav;

use App\Http\Dav\AddressBookBackend;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\PropPatch;
use Tests\TestCase;

class AddressBookBackendTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_one_address_book_per_vault_of_the_account(): void
    {
        $user = User::factory()->create();
        $scranton = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Scranton']);
        Vault::factory()->create(['account_id' => User::factory()->create()->account_id, 'name' => 'Utica']);

        $books = new AddressBookBackend($user)->getAddressBooksForUser('principals/'.$user->email);

        $this->assertCount(1, $books);
        $this->assertEquals($scranton->id, $books[0]['id']);
        $this->assertEquals($scranton->uuid, $books[0]['uri']);
        $this->assertEquals('Scranton', $books[0]['{DAV:}displayname']);
        $this->assertArrayHasKey('{http://calendarserver.org/ns/}getctag', $books[0]);
    }

    #[Test]
    public function it_lists_a_card_per_person_without_building_one(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        $rows = new AddressBookBackend($user)->getCards($vault->id);

        $this->assertCount(1, $rows);
        $this->assertEquals($contact->uuid.'.vcf', $rows[0]['uri']);
        $this->assertArrayHasKey('etag', $rows[0]);
        $this->assertArrayNotHasKey('carddata', $rows[0]);
    }

    #[Test]
    public function it_builds_the_card_only_when_one_is_asked_for(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
        ]);

        $card = new AddressBookBackend($user)->getCard($vault->id, $contact->uuid.'.vcf');

        $this->assertStringContainsString('FN:Dwight Schrute', $card['carddata']);
        $this->assertEquals(strlen($card['carddata']), $card['size']);
    }

    #[Test]
    public function it_answers_that_a_card_it_does_not_hold_is_not_there(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->assertFalse(new AddressBookBackend($user)->getCard($vault->id, 'nobody.vcf'));
    }

    #[Test]
    public function it_reads_a_card_at_the_name_the_device_that_made_it_chose(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'dav_uri' => 'dwight-schrute.vcf',
        ]);

        $backend = new AddressBookBackend($user);

        $this->assertEquals('dwight-schrute.vcf', $backend->uriOf($contact));
        $this->assertIsArray($backend->getCard($vault->id, 'dwight-schrute.vcf'));
        $this->assertFalse($backend->getCard($vault->id, $contact->uuid.'.vcf'));
    }

    #[Test]
    public function it_hands_over_several_cards_at_once(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $first = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $second = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        $cards = new AddressBookBackend($user)->getMultipleCards($vault->id, [
            $first->uuid.'.vcf',
            $second->uuid.'.vcf',
        ]);

        $this->assertCount(2, $cards);
        $this->assertArrayHasKey('carddata', $cards[0]);
    }

    #[Test]
    public function it_hides_a_vault_of_another_account(): void
    {
        $user = User::factory()->create();
        $theirs = Vault::factory()->create(['account_id' => User::factory()->create()->account_id]);

        $this->expectException(NotFound::class);

        new AddressBookBackend($user)->getCards($theirs->id);
    }

    #[Test]
    public function it_refuses_to_make_an_address_book(): void
    {
        $user = User::factory()->create();

        $this->expectException(Forbidden::class);

        new AddressBookBackend($user)->createAddressBook('principals/'.$user->email, 'new', []);
    }

    #[Test]
    public function it_refuses_to_delete_an_address_book(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(Forbidden::class);

        new AddressBookBackend($user)->deleteAddressBook($vault->id);
    }

    #[Test]
    public function it_writes_nothing_a_device_asks_of_an_address_book(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Scranton']);
        $propPatch = new PropPatch(['{DAV:}displayname' => 'Utica']);

        new AddressBookBackend($user)->updateAddressBook($vault->id, $propPatch);

        $this->assertFalse($propPatch->commit());
        $this->assertEquals('Scranton', $vault->refresh()->name);
    }

    #[Test]
    public function it_tells_what_changed_in_the_shape_the_standard_asks_for(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        $changes = new AddressBookBackend($user)->getChangesForAddressBook($vault->id, '', 1);

        $this->assertArrayHasKey('syncToken', $changes);
        $this->assertArrayHasKey('added', $changes);
        $this->assertArrayHasKey('modified', $changes);
        $this->assertArrayHasKey('deleted', $changes);
    }

    #[Test]
    public function it_gives_a_card_a_different_version_for_a_key_that_reaches_sensitive_fields(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        $plain = new AddressBookBackend($user)->getCards($vault->id)[0]['etag'];
        $reaching = new AddressBookBackend($user, reachesSensitiveFields: true)->getCards($vault->id)[0]['etag'];

        $this->assertNotEquals($plain, $reaching);
        $this->assertEquals($contact->uuid.'.vcf', new AddressBookBackend($user)->getCards($vault->id)[0]['uri']);
    }

    #[Test]
    public function it_lists_a_group_card_beside_the_people(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Halpert household']);
        GroupMember::factory()->create(['group_id' => $group->id, 'contact_id' => $jim->id]);

        $rows = new AddressBookBackend($user)->getCards($vault->id);
        $card = new AddressBookBackend($user)->getCard($vault->id, $group->uuid.'.vcf');

        $this->assertEqualsCanonicalizing([$jim->uuid.'.vcf', $group->uuid.'.vcf'], array_column($rows, 'uri'));
        $this->assertStringContainsString('KIND:group', $card['carddata']);
        $this->assertStringContainsString("MEMBER:urn:uuid:{$jim->uuid}", $card['carddata']);
    }

    #[Test]
    public function it_finds_a_group_card_under_the_name_a_device_chose_and_not_under_its_uuid(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'dav_uri' => 'halperts.vcf']);

        $backend = new AddressBookBackend($user);

        $this->assertIsArray($backend->getCard($vault->id, 'halperts.vcf'));
        $this->assertFalse($backend->getCard($vault->id, $group->uuid.'.vcf'));
        $this->assertSame(['halperts.vcf'], array_column($backend->getMultipleCards($vault->id, ['halperts.vcf']), 'uri'));
    }

    #[Test]
    public function it_gives_a_group_card_a_new_version_when_a_member_leaves_the_address_book(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $pam = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        GroupMember::factory()->create(['group_id' => $group->id, 'contact_id' => $pam->id]);

        $before = new AddressBookBackend($user)->getCard($vault->id, $group->uuid.'.vcf')['etag'];

        $pam->update(['is_archived' => true]);

        $this->assertNotEquals($before, new AddressBookBackend($user)->getCard($vault->id, $group->uuid.'.vcf')['etag']);
    }

    #[Test]
    public function it_holds_no_group_card_while_groups_are_off_for_the_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $user->account->features()->deactivate('groups');
        Feature::flushCache();

        $backend = new AddressBookBackend($user);

        $this->assertSame([], $backend->getCards($vault->id));
        $this->assertFalse($backend->getCard($vault->id, $group->uuid.'.vcf'));
        $this->assertSame([], $backend->getChangesForAddressBook($vault->id, '', 1)['added']);
    }
}
