<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Exceptions\EntityNeedsAName;
use App\Helpers\LocaleCollation;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\FieldValue;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntityTest extends TestCase
{
    use RefreshDatabase;

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
    public function it_finds_the_records_of_the_contact_vault_naming_the_contact(): void
    {
        $vault = Vault::factory()->create();
        $angela = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id]);
        $sprinkles = Entity::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'name' => 'Sprinkles']);
        $bandit = Entity::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'name' => 'Bandit']);
        $elsewhere = Entity::factory()->create(['account_id' => $vault->account_id, 'name' => 'Garbage']);
        $this->naming($sprinkles, $angela);
        $this->naming($bandit, $angela, isDetached: true);
        $this->naming($elsewhere, $angela);

        $this->assertSame(['Sprinkles'], Entity::query()->naming($angela)->pluck('name')->all());
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
    public function it_sends_the_search_engine_its_name_and_its_entity_type(): void
    {
        $entity = Entity::factory()->create(['name' => 'Schrute Farms']);

        $document = $entity->toSearchableArray();

        $this->assertSame('Schrute Farms', $document['name']);
        $this->assertSame($entity->entity_type_id, $document['entity_type_id']);
        $this->assertSame($entity->vault_id, $document['vault_id']);
        $this->assertSame('', $document['linked_names']);
    }

    private function naming(Entity $entity, Contact $contact, bool $isDetached = false): void
    {
        FieldValue::factory()->create([
            'account_id' => $entity->account_id,
            'vault_id' => $entity->vault_id,
            'valuable_type' => Entity::class,
            'valuable_id' => $entity->id,
            'value_string' => null,
            'related_contact_id' => $contact->id,
            'is_detached' => $isDetached,
        ]);
    }
}
