<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Dav;

use App\Http\Dav\CollectionChanges;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CollectionChangesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_the_token_of_a_collection_nothing_lives_in_as_nothing(): void
    {
        $changes = $this->changesIn($this->vault());

        $this->assertEquals('0', $changes->token());
    }

    #[Test]
    public function it_reads_the_token_from_the_last_change_the_vault_saw(): void
    {
        $vault = $this->vault();
        $contact = Contact::factory()->create($this->inVault($vault));

        $this->assertEquals(
            (string) $contact->updated_at->getTimestamp(),
            $this->changesIn($vault)->token(),
        );
    }

    #[Test]
    public function it_reads_the_token_from_a_removal_when_that_is_the_last_thing_that_happened(): void
    {
        $vault = $this->vault();
        $contact = Contact::factory()->create($this->inVault($vault));

        $this->travel(5)->seconds();

        $contact->delete();

        $this->assertEquals(
            (string) $contact->refresh()->deleted_at->getTimestamp(),
            $this->changesIn($vault)->token(),
        );
    }

    #[Test]
    public function it_hands_over_everybody_when_a_device_has_never_looked(): void
    {
        $vault = $this->vault();
        $first = Contact::factory()->create($this->inVault($vault));
        $second = Contact::factory()->create($this->inVault($vault));

        $changes = $this->changesIn($vault)->since(null);

        $this->assertEqualsCanonicalizing(
            [$first->uuid, $second->uuid],
            $changes['added'],
        );
        $this->assertEmpty($changes['modified']);
        $this->assertEmpty($changes['deleted']);
    }

    #[Test]
    public function it_tells_what_was_added_changed_and_removed_since_a_token(): void
    {
        $vault = $this->vault();
        $untouched = Contact::factory()->create($this->inVault($vault));
        $changed = Contact::factory()->create($this->inVault($vault));
        $removed = Contact::factory()->create($this->inVault($vault));

        $this->travel(5)->seconds();

        Contact::factory()->create($this->inVault($vault, ['first_name' => 'Kevin']));

        $token = $this->changesIn($vault)->token();

        $this->travel(5)->seconds();

        $added = Contact::factory()->create($this->inVault($vault));
        $changed->displayNameRepresentation->update(['given_name' => 'Dwight']);
        $removed->delete();

        $changes = $this->changesIn($vault)->since($token);

        $this->assertContains($added->uuid, $changes['added']);
        $this->assertEquals([$changed->uuid], $changes['modified']);
        $this->assertEquals([$removed->uuid], $changes['deleted']);
        $this->assertNotContains($untouched->uuid, $changes['added']);
        $this->assertNotContains($untouched->uuid, $changes['modified']);
    }

    #[Test]
    public function it_reads_an_archived_contact_as_one_that_is_gone(): void
    {
        $vault = $this->vault();
        $contact = Contact::factory()->create($this->inVault($vault));

        $this->travel(5)->seconds();

        $token = $this->changesIn($vault)->token();

        $this->travel(5)->seconds();

        $contact->update(['is_archived' => true]);

        $changes = $this->changesIn($vault)->since($token);

        $this->assertEquals([$contact->uuid], $changes['deleted']);
        $this->assertEmpty($changes['modified']);
    }

    #[Test]
    public function it_hands_back_a_change_that_shares_the_second_of_the_token(): void
    {
        $vault = $this->vault();
        $contact = Contact::factory()->create($this->inVault($vault));

        $changes = $this->changesIn($vault)->since($this->changesIn($vault)->token());

        $this->assertEquals([$contact->uuid], $changes['added']);
    }

    #[Test]
    public function it_says_nothing_about_another_vault(): void
    {
        $vault = $this->vault();
        $elsewhere = $this->vault();
        Contact::factory()->create($this->inVault($elsewhere));

        $changes = $this->changesIn($vault)->since(null);

        $this->assertEmpty($changes['added']);
        $this->assertEquals('0', $this->changesIn($vault)->token());
    }

    #[Test]
    public function it_moves_the_token_when_a_group_changes_or_is_deleted(): void
    {
        $vault = $this->vault();
        Contact::factory()->create($this->inVault($vault));

        $this->travel(5)->seconds();
        $group = Group::factory()->create(['vault_id' => $vault->id]);

        $this->assertEquals((string) $group->updated_at->getTimestamp(), $this->changesWithGroupsIn($vault)->token());

        $this->travel(5)->seconds();
        $group->delete();

        $this->assertEquals((string) $group->refresh()->deleted_at->getTimestamp(), $this->changesWithGroupsIn($vault)->token());
        $this->assertNotEquals($this->changesWithGroupsIn($vault)->token(), $this->changesIn($vault)->token());
    }

    #[Test]
    public function it_tells_which_group_cards_were_added_changed_or_removed(): void
    {
        $vault = $this->vault();
        $changed = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Scranton branch']);
        $stayed = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Stamford branch']);
        $removed = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Nashua branch']);

        $this->assertEqualsCanonicalizing(
            [$changed->uuid, $stayed->uuid, $removed->uuid],
            $this->changesWithGroupsIn($vault)->since(null)['added'],
        );

        $this->travel(5)->seconds();
        $since = (string) now()->getTimestamp();

        $changed->touch();
        $removed->delete();
        $added = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Utica branch']);

        $changes = $this->changesWithGroupsIn($vault)->since($since);

        $this->assertSame([$added->uuid], $changes['added']);
        $this->assertSame([$changed->uuid], $changes['modified']);
        $this->assertSame([$removed->uuid], $changes['deleted']);
    }

    #[Test]
    public function it_tells_a_group_card_changed_when_a_current_member_leaves_the_address_book(): void
    {
        $vault = $this->vault();
        $halperts = Group::factory()->create(['vault_id' => $vault->id]);
        $party = Group::factory()->create(['vault_id' => $vault->id]);
        $jim = Contact::factory()->create($this->inVault($vault, ['first_name' => 'Jim']));
        GroupMember::factory()->create(['group_id' => $halperts->id, 'contact_id' => $jim->id]);
        GroupMember::factory()->former()->create(['group_id' => $party->id, 'contact_id' => $jim->id]);

        $this->travel(5)->seconds();
        $since = (string) now()->getTimestamp();

        $jim->delete();

        $this->assertContains($halperts->uuid, $this->changesWithGroupsIn($vault)->since($since)['modified']);
        $this->assertNotContains($party->uuid, $this->changesWithGroupsIn($vault)->since($since)['modified']);
    }

    #[Test]
    public function it_tells_nothing_about_groups_to_a_collection_that_holds_none(): void
    {
        $vault = $this->vault();
        Group::factory()->create(['vault_id' => $vault->id]);

        $this->assertEmpty($this->changesIn($vault)->since(null)['added']);
        $this->assertEquals('0', $this->changesIn($vault)->token());
    }

    private function changesWithGroupsIn(Vault $vault): CollectionChanges
    {
        return new CollectionChanges($vault, fn (Contact $contact): array => [$contact->uuid], fn (Group $group): string => $group->uuid);
    }

    private function vault(): Vault
    {
        return Vault::factory()->create(['account_id' => User::factory()->create()->account_id]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function inVault(Vault $vault, array $attributes = []): array
    {
        return [...$attributes, 'vault_id' => $vault->id, 'account_id' => $vault->account_id];
    }

    private function changesIn(Vault $vault): CollectionChanges
    {
        return new CollectionChanges($vault, fn (Contact $contact): array => [$contact->uuid]);
    }
}
