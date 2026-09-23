<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\RelationshipTypeEnum;
use App\Models\Account;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gets_an_unguessable_identifier(): void
    {
        $type = RelationshipType::factory()->create();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $type->uuid,
        );
    }

    #[Test]
    public function a_supplied_type_reads_the_wording_the_product_gave_it(): void
    {
        $type = RelationshipType::factory()->system(RelationshipTypeEnum::ParentChild)->create();

        $this->assertEquals('Parent / Child', $type->name);
        $this->assertEquals('Parent', $type->role_a);
        $this->assertEquals('Child', $type->role_b);
        $this->assertFalse($type->is_symmetric);
    }

    #[Test]
    public function the_words_the_user_wrote_win_over_the_supplied_wording(): void
    {
        $type = RelationshipType::factory()->system(RelationshipTypeEnum::Friend)->create([
            'name' => 'Best friend',
            'role_a' => 'Best friend',
            'role_b' => 'Best friend',
        ]);

        $this->assertEquals('Best friend', $type->name);
        $this->assertEquals('Best friend', $type->role_a);
        $this->assertEquals('Best friend', $type->role_b);
    }

    #[Test]
    public function an_ordinary_type_carries_no_behaviour(): void
    {
        $type = RelationshipType::factory()->create();

        $this->assertNull($type->system_behavior);
    }

    #[Test]
    public function nothing_is_written_with_a_type_nobody_has_used(): void
    {
        $type = RelationshipType::factory()->create();

        $this->assertEquals(0, $type->relationshipCount());
    }

    #[Test]
    public function it_counts_the_relationships_written_with_it(): void
    {
        $type = RelationshipType::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $type->account_id]);

        Relationship::factory()->count(2)->create([
            'account_id' => $type->account_id,
            'vault_id' => $vault->id,
            'relationship_type_id' => $type->id,
        ]);

        $this->assertEquals(2, $type->relationshipCount());
    }

    #[Test]
    public function a_relationship_that_was_deleted_no_longer_uses_the_type(): void
    {
        $type = RelationshipType::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $type->account_id]);
        $relationship = Relationship::factory()->create([
            'account_id' => $type->account_id,
            'vault_id' => $vault->id,
            'relationship_type_id' => $type->id,
        ]);

        $relationship->delete();

        $this->assertEquals(0, $type->relationshipCount());
    }

    #[Test]
    public function only_the_offered_types_are_left_for_the_picker(): void
    {
        $account = Account::factory()->create();
        $offered = RelationshipType::factory()->create(['account_id' => $account->id, 'name' => 'Friend']);
        RelationshipType::factory()->inactive()->create(['account_id' => $account->id, 'name' => 'Fiancé']);

        $picked = RelationshipType::query()->where('account_id', $account->id)->active()->get();

        $this->assertCount(1, $picked);
        $this->assertTrue($offered->is($picked->first()));
    }

    #[Test]
    public function it_is_deleted_softly(): void
    {
        $type = RelationshipType::factory()->create();

        $type->delete();

        $this->assertSoftDeleted($type);
    }
}
