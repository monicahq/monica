<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Exceptions\EntityNeedsAName;
use App\Helpers\LocaleCollation;
use App\Models\Entity;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gets_an_unguessable_identifier_and_keeps_a_number_for_a_key(): void
    {
        $entity = Entity::factory()->create();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $entity->uuid,
        );
        $this->assertIsInt($entity->id);
        $this->assertEquals('uuid', $entity->getRouteKeyName());
        $this->assertEquals($entity->uuid, $entity->getRouteKey());
    }

    #[Test]
    public function it_writes_the_string_the_lists_sort_on_whenever_it_is_saved(): void
    {
        $entity = Entity::factory()->create(['name' => 'Dunder Mifflin']);

        $this->assertEquals('dunder mifflin', $entity->sort_name);

        $entity->name = 'Vance Refrigeration';
        $entity->save();

        $this->assertEquals('vance refrigeration', $entity->fresh()->sort_name);
    }

    #[Test]
    public function it_refuses_a_record_with_no_name_at_all(): void
    {
        $this->expectException(EntityNeedsAName::class);

        Entity::factory()->create(['name' => '   ']);
    }

    #[Test]
    public function it_builds_the_initials_drawn_on_the_generated_avatar(): void
    {
        $entity = Entity::factory()->make(['name' => 'Dunder Mifflin Paper Company']);

        $this->assertEquals('DM', $entity->initials);
    }

    #[Test]
    public function it_builds_one_initial_when_there_is_only_one_word_to_take_it_from(): void
    {
        $entity = Entity::factory()->make(['name' => 'Sprinkles']);

        $this->assertEquals('S', $entity->initials);
    }

    #[Test]
    public function it_lists_only_the_records_that_are_not_archived(): void
    {
        $vault = Vault::factory()->create();
        $listed = Entity::factory()->create(['vault_id' => $vault->id, 'name' => 'Dunder Mifflin']);
        Entity::factory()->archived()->create(['vault_id' => $vault->id, 'name' => 'Prince Family Paper']);

        $entities = Entity::query()->listed()->get();

        $this->assertCount(1, $entities);
        $this->assertTrue($listed->is($entities->first()));
    }

    #[Test]
    public function it_finds_the_records_whose_name_holds_what_was_typed(): void
    {
        $vault = Vault::factory()->create();
        $sprinkles = Entity::factory()->create(['vault_id' => $vault->id, 'name' => 'Sprinkles']);
        Entity::factory()->create(['vault_id' => $vault->id, 'name' => 'Bandit']);

        $found = Entity::query()->matchingSearch('SPRINK')->get();

        $this->assertCount(1, $found);
        $this->assertTrue($sprinkles->is($found->first()));
        $this->assertCount(2, Entity::query()->matchingSearch('  ')->get());
    }

    #[Test]
    public function it_is_reachable_only_through_the_vault_it_lives_in(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $another = Vault::factory()->create(['account_id' => $user->account_id]);
        $entity = Entity::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
        ]);

        $this->assertTrue($entity->isReachableThrough($user, $vault));
        $this->assertFalse($entity->isReachableThrough($user, $another));
    }

    #[Test]
    public function it_is_deleted_softly(): void
    {
        $entity = Entity::factory()->create(['name' => 'Dunder Mifflin']);

        $entity->delete();

        $this->assertSoftDeleted('entities', ['id' => $entity->id]);
        $this->assertEquals(0, Entity::query()->count());
        $this->assertEquals(1, Entity::query()->withTrashed()->count());
    }

    #[Test]
    public function it_derives_the_two_ordering_keys_when_it_saves(): void
    {
        $entity = Entity::factory()->create(['name' => 'Élodie']);

        $this->assertSame(
            LocaleCollation::sortKey($entity->sort_name, $entity->account->locale()),
            $entity->sort_key,
        );
        $this->assertSame(
            LocaleCollation::foldForSearch($entity->sort_name, $entity->account->locale()),
            $entity->search_key,
        );
    }

    #[Test]
    public function it_rebuilds_the_two_ordering_keys_when_the_name_changes(): void
    {
        $entity = Entity::factory()->create(['name' => 'Dunder']);
        $before = $entity->sort_key;

        $entity->name = 'Mifflin';
        $entity->save();

        $this->assertNotSame($before, $entity->fresh()->sort_key);
    }

    #[Test]
    public function it_does_not_let_the_two_ordering_keys_be_mass_assigned(): void
    {
        $entity = Entity::factory()->create();

        $this->assertNotContains('sort_key', $entity->getFillable());
        $this->assertNotContains('search_key', $entity->getFillable());
    }
}
