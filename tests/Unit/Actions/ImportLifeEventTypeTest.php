<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\AddSectionToStructureVersion;
use App\Actions\CreateLifeEventType;
use App\Actions\CreateStructureVersionDraft;
use App\Actions\ImportLifeEventType;
use App\Actions\PublishStructureVersion;
use App\Enums\StructureAppliesToEnum;
use App\Helpers\StructureFile;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use App\Exceptions\StructureFileIsNotReadable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class ImportLifeEventTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_builds_the_life_event_type_the_file_describes(): void
    {
        Queue::fake();

        $installer = User::factory()->create();
        LifeEventCategory::factory()->create(['account_id' => $installer->account_id, 'name' => 'Leisure and culture']);

        $lifeEventType = new ImportLifeEventType(user: $installer, raw: $this->exported())->execute();

        $this->assertInstanceOf(LifeEventType::class, $lifeEventType);
        $this->assertEquals('Restaurant', $lifeEventType->name);
        $this->assertEquals('Leisure and culture', $lifeEventType->lifeEventCategory->name);
    }

    #[Test]
    public function it_gives_the_copy_a_structure_of_its_own_with_the_same_fields(): void
    {
        Queue::fake();

        $installer = User::factory()->create();
        LifeEventCategory::factory()->create(['account_id' => $installer->account_id, 'name' => 'Leisure and culture']);

        $lifeEventType = new ImportLifeEventType(user: $installer, raw: $this->exported())->execute();
        $version = $lifeEventType->structure->publishedVersion;

        $this->assertEquals(StructureAppliesToEnum::LifeEvent, $lifeEventType->structure->applies_to);
        $this->assertNotNull($version);
        $this->assertEquals(
            ['venue'],
            $version->assembly->first()->sectionVersion->fields->pluck('key')->all(),
        );
    }

    #[Test]
    public function the_copy_is_independent_of_the_original(): void
    {
        Queue::fake();

        $installer = User::factory()->create();
        LifeEventCategory::factory()->create(['account_id' => $installer->account_id, 'name' => 'Leisure and culture']);

        $original = $this->original();
        $copy = new ImportLifeEventType(user: $installer, raw: json_encode(StructureFile::ofLifeEventType($original), JSON_THROW_ON_ERROR))->execute();

        $copy->update(['name' => 'Dinner out']);

        $this->assertEquals('Restaurant', $original->refresh()->name);
        $this->assertNotEquals($original->account_id, $copy->account_id);
        $this->assertNotEquals($original->structure->id, $copy->structure->id);
    }

    #[Test]
    public function it_files_the_copy_under_the_category_it_is_told_to(): void
    {
        Queue::fake();

        $installer = User::factory()->create();
        LifeEventCategory::factory()->create(['account_id' => $installer->account_id, 'name' => 'Leisure and culture']);
        $chosen = LifeEventCategory::factory()->create(['account_id' => $installer->account_id, 'name' => 'Evenings out']);

        $lifeEventType = new ImportLifeEventType(
            user: $installer,
            raw: $this->exported(),
            lifeEventCategory: $chosen,
        )->execute();

        $this->assertEquals($chosen->id, $lifeEventType->life_event_category_id);
    }

    #[Test]
    public function it_falls_back_to_other_when_no_category_matches(): void
    {
        Queue::fake();

        $installer = User::factory()->create();
        LifeEventCategory::factory()->create(['account_id' => $installer->account_id, 'name' => 'Work', 'key' => 'work']);
        $other = LifeEventCategory::factory()->create(['account_id' => $installer->account_id, 'name' => 'Other', 'key' => 'other']);

        $lifeEventType = new ImportLifeEventType(user: $installer, raw: $this->exported())->execute();

        $this->assertEquals($other->id, $lifeEventType->life_event_category_id);
    }

    #[Test]
    public function it_refuses_a_category_from_another_account(): void
    {
        Queue::fake();

        $installer = User::factory()->create();
        $theirs = LifeEventCategory::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new ImportLifeEventType(user: $installer, raw: $this->exported(), lifeEventCategory: $theirs)->execute();
    }

    #[Test]
    public function it_files_the_copy_under_the_supplied_category_of_the_same_key_whatever_it_is_called(): void
    {
        Queue::fake();

        $installer = User::factory()->create();
        LifeEventCategory::factory()->create(['account_id' => $installer->account_id, 'name' => 'Other', 'key' => 'other']);
        $leisure = LifeEventCategory::factory()->create(['account_id' => $installer->account_id, 'name' => 'Loisirs et culture', 'key' => 'leisure']);
        $original = $this->original();
        $original->lifeEventCategory->update(['key' => 'leisure']);

        $lifeEventType = new ImportLifeEventType(
            user: $installer,
            raw: json_encode(StructureFile::ofLifeEventType($original->refresh()), JSON_THROW_ON_ERROR),
        )->execute();

        $this->assertEquals($leisure->id, $lifeEventType->life_event_category_id);
    }

    #[Test]
    public function it_names_a_supplied_type_after_the_wording_it_was_supplied_with(): void
    {
        Queue::fake();

        $installer = User::factory()->create();
        LifeEventCategory::factory()->create(['account_id' => $installer->account_id, 'name' => 'Leisure and culture']);
        $original = $this->original();
        $original->update(['key' => 'call', 'name' => null, 'name_translation_key' => 'Call', 'is_system' => true]);

        $lifeEventType = new ImportLifeEventType(
            user: $installer,
            raw: json_encode(StructureFile::ofLifeEventType($original->refresh()), JSON_THROW_ON_ERROR),
        )->execute();

        $this->assertEquals('Call', $lifeEventType->name);
        $this->assertNull($lifeEventType->key);
        $this->assertFalse($lifeEventType->is_system);
    }

    #[Test]
    public function it_gives_a_second_copy_the_next_position(): void
    {
        Queue::fake();

        $installer = User::factory()->create();
        LifeEventCategory::factory()->create(['account_id' => $installer->account_id, 'name' => 'Leisure and culture']);
        $file = $this->exported();

        $first = new ImportLifeEventType(user: $installer, raw: $file)->execute();
        $second = new ImportLifeEventType(user: $installer, raw: $file)->execute();

        $this->assertEquals(1, $first->position);
        $this->assertEquals(2, $second->position);
        $this->assertNotEquals($first->structure->id, $second->structure->id);
    }

    #[Test]
    public function it_refuses_a_file_it_cannot_read(): void
    {
        Queue::fake();

        $installer = User::factory()->create();

        $this->expectException(StructureFileIsNotReadable::class);

        new ImportLifeEventType(user: $installer, raw: '{"monica": {"format": "template"}}')->execute();
    }

    private function exported(): string
    {
        return json_encode(StructureFile::ofLifeEventType($this->original()), JSON_THROW_ON_ERROR);
    }

    private function original(): LifeEventType
    {
        $author = User::factory()->create();

        $lifeEventType = new CreateLifeEventType(
            user: $author,
            lifeEventCategory: LifeEventCategory::factory()->create([
                'account_id' => $author->account_id,
                'name' => 'Leisure and culture',
            ]),
            name: 'Restaurant',
        )->execute();

        $draft = new CreateStructureVersionDraft(
            user: $author,
            structureVersion: $lifeEventType->structure->publishedVersion,
        )->execute();

        $sectionVersion = SectionVersion::factory()->create([
            'account_id' => $author->account_id,
            'section_id' => Section::factory()->create(['account_id' => $author->account_id])->id,
        ]);

        Field::factory()->create([
            'account_id' => $author->account_id,
            'section_version_id' => $sectionVersion->id,
            'key' => 'venue',
        ]);

        new AddSectionToStructureVersion(
            user: $author,
            structureVersion: $draft,
            sectionVersion: $sectionVersion,
        )->execute();

        new PublishStructureVersion(
            user: $author,
            structureVersion: $draft,
            changeNote: 'Where we went.',
        )->execute();

        return $lifeEventType->refresh();
    }
}
