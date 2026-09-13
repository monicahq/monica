<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\DefaultFieldEnum;
use App\Enums\RelationshipTypeEnum;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\Relationship;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\RelationshipsBlock;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipsBlockTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_holds_nothing_when_nobody_is_connected(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $judy = $this->contactOn($vault, 'Judy', 'Geller');

        $block = new RelationshipsBlock(vault: $vault, contact: $judy);

        $this->assertTrue($block->holdsNothing());
        $this->assertSame(0, $block->total());
        $this->assertStringContainsString('Judy', $block->emptyLine());
    }

    #[Test]
    public function it_gathers_the_links_under_their_categories_in_the_accounts_order(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $judy = $this->contactOn($vault, 'Judy', 'Geller');

        $work = $this->categoryOf($user, 'Professional', 2);
        $family = $this->categoryOf($user, 'Family', 1);

        $this->link($vault, $judy, $this->contactOn($vault, 'Leonard', 'Hayes'), $this->typeOf($user, RelationshipTypeEnum::Colleague, $work));
        $this->link($vault, $judy, $this->contactOn($vault, 'Ross', 'Geller'), $this->typeOf($user, RelationshipTypeEnum::ParentChild, $family));

        $groups = new RelationshipsBlock(vault: $vault, contact: $judy)->groups();

        $this->assertCount(2, $groups);
        $this->assertSame('Family', $groups[0]['label']);
        $this->assertSame('Professional', $groups[1]['label']);
    }

    #[Test]
    public function it_counts_the_people_in_each_group(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $judy = $this->contactOn($vault, 'Judy', 'Geller');
        $family = $this->categoryOf($user, 'Family', 1);
        $type = $this->typeOf($user, RelationshipTypeEnum::ParentChild, $family);

        $this->link($vault, $judy, $this->contactOn($vault, 'Ross', 'Geller'), $type);
        $this->link($vault, $judy, $this->contactOn($vault, 'Monica', 'Geller'), $type);

        $block = new RelationshipsBlock(vault: $vault, contact: $judy);

        $this->assertSame(2, $block->groups()[0]['count']);
        $this->assertSame(2, $block->total());
    }

    #[Test]
    public function it_reads_the_role_from_this_contacts_side(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $judy = $this->contactOn($vault, 'Judy', 'Geller');
        $ross = $this->contactOn($vault, 'Ross', 'Geller');
        $type = $this->typeOf($user, RelationshipTypeEnum::ParentChild, $this->categoryOf($user, 'Family', 1));

        $this->link($vault, $judy, $ross, $type);

        $fromJudy = new RelationshipsBlock(vault: $vault, contact: $judy)->groups()[0]['rows'][0];
        $fromRoss = new RelationshipsBlock(vault: $vault, contact: $ross)->groups()[0]['rows'][0];

        $this->assertSame('Ross Geller', $fromJudy['name']);
        $this->assertSame($type->role_b, $fromJudy['role']);
        $this->assertSame('Judy Geller', $fromRoss['name']);
        $this->assertSame($type->role_a, $fromRoss['role']);
    }

    #[Test]
    public function it_leads_nowhere_for_somebody_who_is_only_a_name(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $judy = $this->contactOn($vault, 'Judy', 'Geller');
        $ruth = $this->contactOn($vault, 'Ruth', 'Geller');
        $ruth->update(['is_partial' => true]);

        $this->link($vault, $judy, $ruth, $this->typeOf($user, RelationshipTypeEnum::ParentChild, $this->categoryOf($user, 'Family', 1)));

        $row = new RelationshipsBlock(vault: $vault, contact: $judy)->groups()[0]['rows'][0];

        $this->assertNull($row['url']);
        $this->assertNull($row['age']);
        $this->assertSame('Name only, no profile yet', $row['note']);
    }

    #[Test]
    public function it_shows_an_age_when_the_year_is_known_and_none_when_it_is_not(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $judy = $this->contactOn($vault, 'Judy', 'Geller');
        $ross = $this->contactOn($vault, 'Ross', 'Geller');
        $monica = $this->contactOn($vault, 'Monica', 'Geller');
        $type = $this->typeOf($user, RelationshipTypeEnum::ParentChild, $this->categoryOf($user, 'Family', 1));

        $this->birthday($ross, CarbonImmutable::now()->subYears(59)->startOfYear());

        $this->link($vault, $judy, $ross, $type);
        $this->link($vault, $judy, $monica, $type);

        $rows = collect(new RelationshipsBlock(vault: $vault, contact: $judy)->groups()[0]['rows'])
            ->keyBy('name');

        $this->assertSame(59, $rows['Ross Geller']['age']);
        $this->assertNull($rows['Monica Geller']['age']);
    }

    #[Test]
    public function it_says_when_a_link_has_ended_and_what_dates_it_holds(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $judy = $this->contactOn($vault, 'Judy', 'Geller');
        $jack = $this->contactOn($vault, 'Jack', 'Geller');
        $type = $this->typeOf($user, RelationshipTypeEnum::Partner, $this->categoryOf($user, 'Romantic', 1));

        Relationship::factory()->ended()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'relationship_type_id' => $type->id,
            'contact_a_id' => $judy->id,
            'contact_b_id' => $jack->id,
            'started_on' => '1965-04-01',
            'ended_on' => '2001-06-30',
        ]);

        $row = new RelationshipsBlock(vault: $vault, contact: $judy)->groups()[0]['rows'][0];

        $this->assertTrue($row['hasEnded']);
        $this->assertNotNull($row['whenLine']);
    }

    #[Test]
    public function it_says_nothing_about_dates_a_link_does_not_hold(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $judy = $this->contactOn($vault, 'Judy', 'Geller');
        $jack = $this->contactOn($vault, 'Jack', 'Geller');

        Relationship::factory()->ended()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'relationship_type_id' => $this->typeOf($user, RelationshipTypeEnum::Partner, $this->categoryOf($user, 'Romantic', 1))->id,
            'contact_a_id' => $judy->id,
            'contact_b_id' => $jack->id,
        ]);

        $row = new RelationshipsBlock(vault: $vault, contact: $judy)->groups()[0]['rows'][0];

        $this->assertTrue($row['hasEnded']);
        $this->assertNull($row['whenLine']);
    }

    #[Test]
    public function it_counts_how_many_linked_people_are_waiting_for_a_record(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $judy = $this->contactOn($vault, 'Judy', 'Geller');
        $type = $this->typeOf($user, RelationshipTypeEnum::ParentChild, $this->categoryOf($user, 'Family', 1));

        $ruth = $this->contactOn($vault, 'Ruth', 'Geller');
        $ruth->update(['is_partial' => true]);

        $this->link($vault, $judy, $ruth, $type);
        $this->link($vault, $judy, $this->contactOn($vault, 'Ross', 'Geller'), $type);

        $this->assertSame(1, new RelationshipsBlock(vault: $vault, contact: $judy)->namesWaiting());
    }

    #[Test]
    public function it_counts_nobody_waiting_when_everybody_has_a_record(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $judy = $this->contactOn($vault, 'Judy', 'Geller');

        $this->link($vault, $judy, $this->contactOn($vault, 'Ross', 'Geller'), $this->typeOf($user, RelationshipTypeEnum::ParentChild, $this->categoryOf($user, 'Family', 1)));

        $this->assertSame(0, new RelationshipsBlock(vault: $vault, contact: $judy)->namesWaiting());
    }

    #[Test]
    public function it_answers_twice_for_a_pair_linked_under_two_natures(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $judy = $this->contactOn($vault, 'Judy', 'Geller');
        $ross = $this->contactOn($vault, 'Ross', 'Geller');
        $family = $this->categoryOf($user, 'Family', 1);

        $this->link($vault, $judy, $ross, $this->typeOf($user, RelationshipTypeEnum::ParentChild, $family));
        $this->link($vault, $judy, $ross, $this->typeOf($user, RelationshipTypeEnum::Sibling, $family));

        $this->assertSame(2, new RelationshipsBlock(vault: $vault, contact: $judy)->total());
    }

    private function link(Vault $vault, Contact $one, Contact $other, RelationshipType $type): Relationship
    {
        return Relationship::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'relationship_type_id' => $type->id,
            'contact_a_id' => $one->id,
            'contact_b_id' => $other->id,
        ]);
    }

    private function birthday(Contact $contact, CarbonImmutable $date): void
    {
        FieldValue::factory()->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_key' => DefaultFieldEnum::Birthday->value,
            'value_date' => $date,
            'year_is_unknown' => false,
        ]);
    }

    private function contactOn(Vault $vault, string $firstName, string $lastName): Contact
    {
        return Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    private function categoryOf(User $user, string $name, int $position): RelationshipCategory
    {
        return RelationshipCategory::factory()->create([
            'account_id' => $user->account_id,
            'name' => $name,
            'position' => $position,
        ]);
    }

    private function typeOf(User $user, RelationshipTypeEnum $case, RelationshipCategory $category): RelationshipType
    {
        return RelationshipType::factory()->system($case)->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
        ]);
    }
}
