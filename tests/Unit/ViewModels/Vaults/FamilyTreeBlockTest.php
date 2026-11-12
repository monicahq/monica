<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\RelationshipTypeEnum;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\FamilyTreeBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FamilyTreeBlockTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_draws_a_parent_a_partner_and_the_children(): void
    {
        $vault = $this->vault();
        $judy = $this->contactOn($vault, 'Judy', 'Geller');
        $ruth = $this->contactOn($vault, 'Ruth', 'Geller');
        $jack = $this->contactOn($vault, 'Jack', 'Geller');
        $ross = $this->contactOn($vault, 'Ross', 'Geller');
        $monica = $this->contactOn($vault, 'Monica', 'Geller');

        $this->parentage($vault, $ruth, $judy);
        $this->romantic($vault, $judy, $jack);
        $this->parentage($vault, $judy, $ross);
        $this->parentage($vault, $judy, $monica);

        $block = new FamilyTreeBlock(vault: $vault, contact: $judy);

        $this->assertSame(['Ruth Geller'], array_column($block->parents(), 'name'));
        $this->assertSame(['Jack Geller'], array_column($block->currentPartners(), 'name'));
        $this->assertSame(['Monica Geller', 'Ross Geller'], array_column($block->children(), 'name'));
        $this->assertFalse($block->holdsNothing());
    }

    #[Test]
    public function it_drops_a_tier_holding_nobody(): void
    {
        $vault = $this->vault();
        $judy = $this->contactOn($vault, 'Judy', 'Geller');

        $this->parentage($vault, $judy, $this->contactOn($vault, 'Ross', 'Geller'));

        $block = new FamilyTreeBlock(vault: $vault, contact: $judy);

        $this->assertSame([], $block->parents());
        $this->assertSame([], $block->currentPartners());
        $this->assertCount(1, $block->children());
        $this->assertFalse($block->holdsNothing());
    }

    #[Test]
    public function it_drops_a_partner_once_the_relationship_ended(): void
    {
        $vault = $this->vault();
        $judy = $this->contactOn($vault, 'Judy', 'Geller');
        $jack = $this->contactOn($vault, 'Jack', 'Geller');

        Relationship::factory()->romantic()->ended()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'contact_a_id' => $judy->id,
            'contact_b_id' => $jack->id,
        ]);

        $this->assertSame([], new FamilyTreeBlock(vault: $vault, contact: $judy)->currentPartners());
    }

    #[Test]
    public function it_keeps_a_parent_and_a_child_whatever_the_status(): void
    {
        $vault = $this->vault();
        $judy = $this->contactOn($vault, 'Judy', 'Geller');
        $ruth = $this->contactOn($vault, 'Ruth', 'Geller');
        $ross = $this->contactOn($vault, 'Ross', 'Geller');

        foreach ([[$ruth, $judy], [$judy, $ross]] as [$parent, $child]) {
            Relationship::factory()->parentage()->ended()->create([
                'account_id' => $vault->account_id,
                'vault_id' => $vault->id,
                'contact_a_id' => $parent->id,
                'contact_b_id' => $child->id,
            ]);
        }

        $block = new FamilyTreeBlock(vault: $vault, contact: $judy);

        $this->assertSame(['Ruth Geller'], array_column($block->parents(), 'name'));
        $this->assertSame(['Ross Geller'], array_column($block->children(), 'name'));
    }

    #[Test]
    public function it_follows_the_behaviour_rather_than_the_name_of_the_type(): void
    {
        $vault = $this->vault();
        $judy = $this->contactOn($vault, 'Judy', 'Geller');
        $ruth = $this->contactOn($vault, 'Ruth', 'Geller');
        $stranger = $this->contactOn($vault, 'Aunt', 'Iris');

        $supplied = RelationshipType::factory()->system(RelationshipTypeEnum::ParentChild)
            ->create(['account_id' => $vault->account_id]);

        $invented = RelationshipType::factory()->create([
            'account_id' => $vault->account_id,
            'name' => 'Parent / Child',
            'role_a' => 'Parent',
            'role_b' => 'Child',
        ]);

        $this->link($vault, $ruth, $judy, $supplied);
        $this->link($vault, $stranger, $judy, $invented);

        $supplied->update(['name' => 'Progenitor', 'name_translation_key' => null, 'is_active' => false]);

        $this->assertSame(['Ruth Geller'], array_column(
            new FamilyTreeBlock(vault: $vault, contact: $judy)->parents(),
            'name',
        ));
    }

    #[Test]
    public function it_draws_no_friend_or_colleague(): void
    {
        $vault = $this->vault();
        $judy = $this->contactOn($vault, 'Judy', 'Geller');

        foreach ([RelationshipTypeEnum::Friend, RelationshipTypeEnum::Colleague, RelationshipTypeEnum::Sibling] as $case) {
            $type = RelationshipType::factory()->system($case)->create(['account_id' => $vault->account_id]);

            $this->link($vault, $judy, $this->contactOn($vault, 'Someone', 'Else'), $type);
        }

        $this->assertTrue(new FamilyTreeBlock(vault: $vault, contact: $judy)->holdsNothing());
    }

    #[Test]
    public function it_offers_no_way_through_to_somebody_who_is_a_name_only(): void
    {
        $vault = $this->vault();
        $judy = $this->contactOn($vault, 'Judy', 'Geller');
        $iris = Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'first_name' => 'Aunt',
            'last_name' => 'Iris',
            'is_partial' => true,
        ]);

        $this->parentage($vault, $iris, $judy);

        $parent = new FamilyTreeBlock(vault: $vault, contact: $judy)->parents()[0];

        $this->assertNull($parent['url']);
        $this->assertTrue($parent['isNameOnly']);
    }

    #[Test]
    public function it_draws_somebody_who_is_both_a_parent_and_a_partner_in_both_tiers(): void
    {
        $vault = $this->vault();
        $judy = $this->contactOn($vault, 'Judy', 'Geller');
        $both = $this->contactOn($vault, 'Jack', 'Geller');

        $this->parentage($vault, $both, $judy);
        $this->romantic($vault, $judy, $both);

        $block = new FamilyTreeBlock(vault: $vault, contact: $judy);

        $this->assertSame(['Jack Geller'], array_column($block->parents(), 'name'));
        $this->assertSame(['Jack Geller'], array_column($block->currentPartners(), 'name'));
    }

    #[Test]
    public function it_says_there_is_no_line_to_draw_and_offers_the_first_relationship(): void
    {
        $vault = $this->vault();
        $judy = $this->contactOn($vault, 'Judy', 'Geller');

        $block = new FamilyTreeBlock(vault: $vault, contact: $judy);

        $this->assertTrue($block->holdsNothing());
        $this->assertStringContainsString('Judy', $block->emptyLine());
        $this->assertSame(
            route('contacts.relationships.new', ['vault' => $vault, 'contact' => $judy]),
            $block->addRelationshipUrl(),
        );
    }

    private function vault(): Vault
    {
        $user = User::factory()->create();

        return Vault::factory()->create(['account_id' => $user->account_id]);
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

    private function parentage(Vault $vault, Contact $parent, Contact $child): void
    {
        Relationship::factory()->parentage()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'contact_a_id' => $parent->id,
            'contact_b_id' => $child->id,
        ]);
    }

    private function romantic(Vault $vault, Contact $one, Contact $other): void
    {
        Relationship::factory()->romantic()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'contact_a_id' => $one->id,
            'contact_b_id' => $other->id,
        ]);
    }

    private function link(Vault $vault, Contact $one, Contact $other, RelationshipType $type): void
    {
        Relationship::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'relationship_type_id' => $type->id,
            'contact_a_id' => $one->id,
            'contact_b_id' => $other->id,
        ]);
    }
}
