<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\DefaultFieldEnum;
use App\Enums\RelationshipTypeEnum;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\Vault;
use App\ViewModels\Vaults\ImmediateFamilyBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImmediateFamilyBlockTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_children_of_a_contact(): void
    {
        $vault = Vault::factory()->create();
        $michael = $this->personIn($vault, 'Michael', 'Scott');
        $child = $this->personIn($vault, 'Pam', 'Beesly');

        $this->parentage($vault, $michael, $child);

        $block = new ImmediateFamilyBlock(vault: $vault, contact: $michael);

        $this->assertSame(1, $block->childCount());
        $this->assertSame('Pam Beesly', $block->children()[0]['name']);
        $this->assertSame(
            route('contacts.show', ['vault' => $vault, 'contact' => $child]),
            $block->children()[0]['url'],
        );
    }

    #[Test]
    public function it_lists_every_current_partner(): void
    {
        $vault = Vault::factory()->create();
        $michael = $this->personIn($vault, 'Michael', 'Scott');
        $holly = $this->personIn($vault, 'Holly', 'Flax');
        $jan = $this->personIn($vault, 'Jan', 'Levinson');

        $this->romance($vault, $michael, $holly);
        $this->romance($vault, $jan, $michael);

        $block = new ImmediateFamilyBlock(vault: $vault, contact: $michael);

        $this->assertSame(2, $block->currentPartnerCount());
        $this->assertSame(
            ['Holly Flax', 'Jan Levinson'],
            array_column($block->currentPartners(), 'name'),
        );
    }

    #[Test]
    public function it_orders_each_list_by_name(): void
    {
        $vault = Vault::factory()->create();
        $michael = $this->personIn($vault, 'Michael', 'Scott');

        foreach ([['Dwight', 'Schrute'], ['Pam', 'Beesly'], ['Jim', 'Halpert']] as [$first, $last]) {
            $this->parentage($vault, $michael, $this->personIn($vault, $first, $last));
        }

        $this->assertSame(
            ['Pam Beesly', 'Jim Halpert', 'Dwight Schrute'],
            array_column((new ImmediateFamilyBlock(vault: $vault, contact: $michael))->children(), 'name'),
        );
    }

    #[Test]
    public function it_names_a_child_written_down_without_a_name_the_way_the_rest_of_the_product_does(): void
    {
        $vault = Vault::factory()->create();
        $michael = $this->personIn($vault, 'Michael', 'Scott');
        $child = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'first_name' => null,
            'last_name' => null,
        ]);

        $this->parentage($vault, $michael, $child);

        $block = new ImmediateFamilyBlock(vault: $vault, contact: $michael);

        $this->assertSame($child->name, $block->children()[0]['name']);
    }

    #[Test]
    public function it_offers_no_way_through_to_somebody_who_is_only_a_name(): void
    {
        $vault = Vault::factory()->create();
        $michael = $this->personIn($vault, 'Michael', 'Scott');
        $child = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'first_name' => 'Jack',
            'last_name' => null,
            'is_partial' => true,
        ]);

        $this->parentage($vault, $michael, $child);

        $row = (new ImmediateFamilyBlock(vault: $vault, contact: $michael))->children()[0];

        $this->assertSame('Jack', $row['name']);
        $this->assertNull($row['url']);
    }

    #[Test]
    public function it_reads_the_age_of_somebody_whose_birthday_carries_a_year(): void
    {
        $vault = Vault::factory()->create();
        $michael = $this->personIn($vault, 'Michael', 'Scott');
        $child = $this->personIn($vault, 'Pam', 'Beesly');

        $this->parentage($vault, $michael, $child);
        $this->birthday($child, now()->subYears(22)->subMonth()->format('Y-m-d'));

        $block = new ImmediateFamilyBlock(vault: $vault, contact: $michael);

        $this->assertSame(22, $block->children()[0]['age']);
    }

    #[Test]
    public function it_reads_no_age_when_the_birthday_year_is_unknown(): void
    {
        $vault = Vault::factory()->create();
        $michael = $this->personIn($vault, 'Michael', 'Scott');
        $child = $this->personIn($vault, 'Pam', 'Beesly');

        $this->parentage($vault, $michael, $child);
        $this->birthday($child, '1900-03-15', yearIsUnknown: true);

        $block = new ImmediateFamilyBlock(vault: $vault, contact: $michael);

        $this->assertNull($block->children()[0]['age']);
    }

    #[Test]
    public function it_reads_no_age_when_no_birthday_is_recorded(): void
    {
        $vault = Vault::factory()->create();
        $michael = $this->personIn($vault, 'Michael', 'Scott');

        $this->parentage($vault, $michael, $this->personIn($vault, 'Pam', 'Beesly'));

        $block = new ImmediateFamilyBlock(vault: $vault, contact: $michael);

        $this->assertNull($block->children()[0]['age']);
    }

    #[Test]
    public function it_holds_nothing_when_no_family_is_written_down(): void
    {
        $vault = Vault::factory()->create();
        $michael = $this->personIn($vault, 'Michael', 'Scott');

        $block = new ImmediateFamilyBlock(vault: $vault, contact: $michael);

        $this->assertTrue($block->holdsNothing());
        $this->assertSame(0, $block->childCount());
        $this->assertSame(0, $block->currentPartnerCount());
    }

    #[Test]
    public function it_opens_the_form_on_the_partner_type_and_on_the_parent_type(): void
    {
        $vault = Vault::factory()->create();
        $michael = $this->personIn($vault, 'Michael', 'Scott');
        $partner = RelationshipType::factory()->system(RelationshipTypeEnum::Partner)->create(['account_id' => $vault->account_id, 'position' => 1]);
        RelationshipType::factory()->system(RelationshipTypeEnum::Spouse)->create(['account_id' => $vault->account_id, 'position' => 2]);
        $parentage = RelationshipType::factory()->system(RelationshipTypeEnum::ParentChild)->create(['account_id' => $vault->account_id]);

        $block = new ImmediateFamilyBlock(vault: $vault, contact: $michael);

        $this->assertSame(route('contacts.relationships.new', ['vault' => $vault, 'contact' => $michael, 'type' => $partner->id]), $block->addPartnerUrl());
        $this->assertSame(route('contacts.relationships.new', ['vault' => $vault, 'contact' => $michael, 'type' => $parentage->id]), $block->addChildUrl());
    }

    #[Test]
    public function it_opens_the_form_without_a_type_when_the_account_no_longer_offers_one(): void
    {
        $vault = Vault::factory()->create();
        $michael = $this->personIn($vault, 'Michael', 'Scott');
        RelationshipType::factory()->system(RelationshipTypeEnum::Partner)->inactive()->create(['account_id' => $vault->account_id]);

        $block = new ImmediateFamilyBlock(vault: $vault, contact: $michael);

        $this->assertSame(route('contacts.relationships.new', ['vault' => $vault, 'contact' => $michael]), $block->addPartnerUrl());
        $this->assertSame(route('contacts.relationships.new', ['vault' => $vault, 'contact' => $michael]), $block->addChildUrl());
    }

    #[Test]
    public function it_names_the_contact_by_first_name_and_draws_their_initials_from_the_whole_name(): void
    {
        $vault = Vault::factory()->create();
        $michael = $this->personIn($vault, 'Michael', 'Scott');

        $block = new ImmediateFamilyBlock(vault: $vault, contact: $michael);

        $this->assertSame('Michael', $block->contactName());
        $this->assertSame($michael->name, $block->contactFullName());
        $this->assertNull($block->contactAvatarUrl());
    }

    #[Test]
    public function it_asks_the_database_no_more_often_for_twenty_people_than_for_one(): void
    {
        $vault = Vault::factory()->create();

        $one = $this->personIn($vault, 'Michael', 'Scott');
        $this->parentage($vault, $one, $this->personIn($vault, 'Pam', 'Beesly'));

        $many = $this->personIn($vault, 'Robert', 'California');

        for ($index = 0; $index < 20; $index++) {
            $child = $this->personIn($vault, "Child{$index}", 'California');
            $this->parentage($vault, $many, $child);
            $this->birthday($child, '1990-01-01');
        }

        $this->warmTheAccountPreferences($vault, $one);

        $this->assertSame($this->queriesDrawing($vault, $one), $this->queriesDrawing($vault, $many));
    }

    /**
     * The first URL built in a request asks the account once whether it writes
     * readable addresses. That lookup is per request rather than per person, so
     * it is drawn here before either measurement rather than counted against
     * whichever list happens to run first.
     */
    private function warmTheAccountPreferences(Vault $vault, Contact $contact): void
    {
        new ImmediateFamilyBlock(vault: $vault, contact: $contact)->children();
    }

    private function queriesDrawing(Vault $vault, Contact $contact): int
    {
        $block = new ImmediateFamilyBlock(vault: $vault, contact: $contact);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $block->children();
        $block->currentPartners();

        $count = count(DB::getRawQueryLog());

        DB::disableQueryLog();

        return $count;
    }

    private function personIn(Vault $vault, string $first, string $last): Contact
    {
        return Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'first_name' => $first,
            'last_name' => $last,
        ]);
    }

    private function parentage(Vault $vault, Contact $parent, Contact $child): void
    {
        Relationship::factory()->parentage()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'contact_a_id' => $parent->id,
            'contact_b_id' => $child->id,
        ]);
    }

    private function romance(Vault $vault, Contact $one, Contact $other): void
    {
        Relationship::factory()->romantic()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'contact_a_id' => $one->id,
            'contact_b_id' => $other->id,
        ]);
    }

    private function birthday(Contact $contact, string $date, bool $yearIsUnknown = false): void
    {
        FieldValue::factory()->recurringDate($date)->create([
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_key' => DefaultFieldEnum::Birthday->value,
            'year_is_unknown' => $yearIsUnknown,
        ]);
    }
}
