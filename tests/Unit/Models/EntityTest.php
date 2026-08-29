<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Exceptions\EntityNeedsAName;
use App\Helpers\LocaleCollation;
use App\Models\Account;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\File;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $account = Account::factory()->create();
        $entity = Entity::factory()->create(['account_id' => $account->id]);

        $this->assertTrue($entity->account()->exists());
        $this->assertTrue($account->is($entity->account));
    }

    #[Test]
    public function it_belongs_to_a_vault(): void
    {
        $vault = Vault::factory()->create();
        $entity = Entity::factory()->create(['vault_id' => $vault->id]);

        $this->assertTrue($entity->vault()->exists());
        $this->assertTrue($vault->is($entity->vault));
    }

    #[Test]
    public function it_belongs_to_the_kind_of_object_it_is(): void
    {
        $type = EntityType::factory()->create(['name_singular' => 'Company']);
        $entity = Entity::factory()->create(['entity_type_id' => $type->id]);

        $this->assertTrue($entity->entityType()->exists());
        $this->assertTrue($type->is($entity->entityType));
    }

    #[Test]
    public function it_belongs_to_the_template_version_its_page_renders_from(): void
    {
        $version = TemplateVersion::factory()->published()->create();
        $entity = Entity::factory()->create(['template_version_id' => $version->id]);

        $this->assertTrue($entity->templateVersion()->exists());
        $this->assertTrue($version->is($entity->templateVersion));
    }

    #[Test]
    public function it_belongs_to_the_file_standing_in_for_it(): void
    {
        $vault = Vault::factory()->create();
        $file = File::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
        ]);
        $entity = Entity::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'avatar_file_id' => $file->id,
        ]);

        $this->assertTrue($entity->avatar()->exists());
        $this->assertTrue($file->is($entity->avatar));
    }

    #[Test]
    public function it_has_no_avatar_when_it_is_drawn_as_the_initials_the_product_generates(): void
    {
        $entity = Entity::factory()->create();

        $this->assertNull($entity->avatar_file_id);
        $this->assertNull($entity->avatar);
    }

    #[Test]
    public function it_has_the_values_written_on_its_page(): void
    {
        $entity = Entity::factory()->create(['name' => 'Dunder Mifflin']);
        FieldValue::factory()->create([
            'account_id' => $entity->account_id,
            'vault_id' => $entity->vault_id,
            'valuable_type' => Entity::class,
            'valuable_id' => $entity->id,
        ]);

        $this->assertTrue($entity->fieldValues()->exists());
        $this->assertCount(1, $entity->fieldValues);
    }

    #[Test]
    public function it_has_the_repetitions_of_the_repeaters_on_its_page(): void
    {
        $entity = Entity::factory()->create(['name' => 'Dunder Mifflin']);
        FieldValueRow::factory()->create([
            'account_id' => $entity->account_id,
            'valuable_type' => Entity::class,
            'valuable_id' => $entity->id,
            'field_id' => Field::factory()->repeater(),
        ]);

        $this->assertTrue($entity->fieldValueRows()->exists());
        $this->assertCount(1, $entity->fieldValueRows);
    }

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
    public function it_casts_the_flag_and_the_dates(): void
    {
        $entity = Entity::factory()->archived()->create(['last_consulted_at' => now()]);

        $entity->refresh();

        $this->assertTrue($entity->is_archived);
        $this->assertFalse($entity->deleted_with_vault);
        $this->assertInstanceOf(Carbon::class, $entity->last_consulted_at);
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
