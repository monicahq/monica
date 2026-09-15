<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\AddSectionToStructureVersion;
use App\Actions\CreateActivityType;
use App\Actions\CreateStructureVersionDraft;
use App\Actions\ImportActivityType;
use App\Actions\PublishStructureVersion;
use App\Enums\StructureAppliesToEnum;
use App\Helpers\StructureFile;
use App\Models\ActivityCategory;
use App\Models\ActivityType;
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

class ImportActivityTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_builds_the_activity_type_the_file_describes(): void
    {
        Queue::fake();

        $installer = User::factory()->create();
        ActivityCategory::factory()->create(['account_id' => $installer->account_id, 'name' => 'Leisure and culture']);

        $activityType = new ImportActivityType(user: $installer, raw: $this->exported())->execute();

        $this->assertInstanceOf(ActivityType::class, $activityType);
        $this->assertEquals('Restaurant', $activityType->name);
        $this->assertEquals('Leisure and culture', $activityType->activityCategory->name);
    }

    #[Test]
    public function it_gives_the_copy_a_structure_of_its_own_with_the_same_fields(): void
    {
        Queue::fake();

        $installer = User::factory()->create();
        ActivityCategory::factory()->create(['account_id' => $installer->account_id, 'name' => 'Leisure and culture']);

        $activityType = new ImportActivityType(user: $installer, raw: $this->exported())->execute();
        $version = $activityType->structure->publishedVersion;

        $this->assertEquals(StructureAppliesToEnum::Activity, $activityType->structure->applies_to);
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
        ActivityCategory::factory()->create(['account_id' => $installer->account_id, 'name' => 'Leisure and culture']);

        $original = $this->original();
        $copy = new ImportActivityType(user: $installer, raw: json_encode(StructureFile::ofActivityType($original), JSON_THROW_ON_ERROR))->execute();

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
        ActivityCategory::factory()->create(['account_id' => $installer->account_id, 'name' => 'Leisure and culture']);
        $chosen = ActivityCategory::factory()->create(['account_id' => $installer->account_id, 'name' => 'Evenings out']);

        $activityType = new ImportActivityType(
            user: $installer,
            raw: $this->exported(),
            activityCategory: $chosen,
        )->execute();

        $this->assertEquals($chosen->id, $activityType->activity_category_id);
    }

    #[Test]
    public function it_falls_back_to_other_when_no_category_matches(): void
    {
        Queue::fake();

        $installer = User::factory()->create();
        ActivityCategory::factory()->create(['account_id' => $installer->account_id, 'name' => 'Work', 'key' => 'work']);
        $other = ActivityCategory::factory()->create(['account_id' => $installer->account_id, 'name' => 'Other', 'key' => 'other']);

        $activityType = new ImportActivityType(user: $installer, raw: $this->exported())->execute();

        $this->assertEquals($other->id, $activityType->activity_category_id);
    }

    #[Test]
    public function it_refuses_a_category_from_another_account(): void
    {
        Queue::fake();

        $installer = User::factory()->create();
        $theirs = ActivityCategory::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new ImportActivityType(user: $installer, raw: $this->exported(), activityCategory: $theirs)->execute();
    }

    #[Test]
    public function it_files_the_copy_under_the_supplied_category_of_the_same_key_whatever_it_is_called(): void
    {
        Queue::fake();

        $installer = User::factory()->create();
        ActivityCategory::factory()->create(['account_id' => $installer->account_id, 'name' => 'Other', 'key' => 'other']);
        $leisure = ActivityCategory::factory()->create(['account_id' => $installer->account_id, 'name' => 'Loisirs et culture', 'key' => 'leisure']);
        $original = $this->original();
        $original->activityCategory->update(['key' => 'leisure']);

        $activityType = new ImportActivityType(
            user: $installer,
            raw: json_encode(StructureFile::ofActivityType($original->refresh()), JSON_THROW_ON_ERROR),
        )->execute();

        $this->assertEquals($leisure->id, $activityType->activity_category_id);
    }

    #[Test]
    public function it_names_a_supplied_type_after_the_wording_it_was_supplied_with(): void
    {
        Queue::fake();

        $installer = User::factory()->create();
        ActivityCategory::factory()->create(['account_id' => $installer->account_id, 'name' => 'Leisure and culture']);
        $original = $this->original();
        $original->update(['key' => 'call', 'name' => null, 'name_translation_key' => 'Call', 'is_system' => true]);

        $activityType = new ImportActivityType(
            user: $installer,
            raw: json_encode(StructureFile::ofActivityType($original->refresh()), JSON_THROW_ON_ERROR),
        )->execute();

        $this->assertEquals('Call', $activityType->name);
        $this->assertNull($activityType->key);
        $this->assertFalse($activityType->is_system);
    }

    #[Test]
    public function it_gives_a_second_copy_the_next_position(): void
    {
        Queue::fake();

        $installer = User::factory()->create();
        ActivityCategory::factory()->create(['account_id' => $installer->account_id, 'name' => 'Leisure and culture']);
        $file = $this->exported();

        $first = new ImportActivityType(user: $installer, raw: $file)->execute();
        $second = new ImportActivityType(user: $installer, raw: $file)->execute();

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

        new ImportActivityType(user: $installer, raw: '{"monica": {"format": "template"}}')->execute();
    }

    private function exported(): string
    {
        return json_encode(StructureFile::ofActivityType($this->original()), JSON_THROW_ON_ERROR);
    }

    private function original(): ActivityType
    {
        $author = User::factory()->create();

        $activityType = new CreateActivityType(
            user: $author,
            activityCategory: ActivityCategory::factory()->create([
                'account_id' => $author->account_id,
                'name' => 'Leisure and culture',
            ]),
            name: 'Restaurant',
        )->execute();

        $draft = new CreateStructureVersionDraft(
            user: $author,
            structureVersion: $activityType->structure->publishedVersion,
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

        return $activityType->refresh();
    }
}
