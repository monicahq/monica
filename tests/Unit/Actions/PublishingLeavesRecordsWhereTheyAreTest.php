<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateLifeEventType;
use App\Actions\CreateStructureVersionDraft;
use App\Actions\PublishStructureVersion;
use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublishingLeavesRecordsWhereTheyAreTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_contact_stays_on_the_version_it_was_written_on(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $first = $this->publishedVersion($user, $template);

        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
            'structure_version_id' => $first->id,
        ]);

        $this->publishAnotherVersion($user, $first);

        $this->assertEquals($first->id, $contact->refresh()->structure_version_id);
    }

    #[Test]
    public function an_entity_stays_on_the_version_it_was_written_on(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->forEntity($entityType)->create(['account_id' => $user->account_id]);
        $first = $this->publishedVersion($user, $template);

        $entity = Entity::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
            'entity_type_id' => $entityType->id,
            'structure_version_id' => $first->id,
        ]);

        $this->publishAnotherVersion($user, $first);

        $this->assertEquals($first->id, $entity->refresh()->structure_version_id);
    }

    #[Test]
    public function a_life_event_type_keeps_offering_the_newest_version_without_touching_the_old_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $lifeEventType = new CreateLifeEventType(
            user: $user,
            lifeEventCategory: LifeEventCategory::factory()->create(['account_id' => $user->account_id]),
            name: 'Restaurant',
        )->execute();

        $first = $lifeEventType->structure->publishedVersion;
        $second = $this->publishAnotherVersion($user, $first);

        $this->assertEquals($second->id, $lifeEventType->structure->refresh()->published_version_id);
        $this->assertEquals($first->id, $second->previous_version_id);
        $this->assertEquals(VersionStatusEnum::Published, $first->refresh()->status);
    }

    #[Test]
    public function a_value_stays_attached_to_the_field_of_the_version_it_was_entered_under(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $first = StructureVersion::factory()->create([
            'account_id' => $user->account_id,
            'structure_id' => $template->structure->id,
        ]);

        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->create([
            'account_id' => $user->account_id,
            'section_id' => $section->id,
        ]);

        $placement = StructureVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'structure_version_id' => $first->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $field = Field::factory()->create([
            'account_id' => $user->account_id,
            'section_version_id' => $placement->section_version_id,
            'key' => 'branch',
        ]);

        new PublishStructureVersion(user: $user, structureVersion: $first)->execute();
        $first->refresh();

        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $first->id,
        ]);

        $value = FieldValue::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'value_string' => 'Scranton',
        ]);

        $this->publishAnotherVersion($user, $first);

        $this->assertEquals('Scranton', $value->refresh()->value_string);
        $this->assertEquals($first->id, $contact->refresh()->structure_version_id);
        $this->assertTrue(
            $value->field->sectionVersion->structureVersions->contains('id', $first->id),
        );
    }

    #[Test]
    public function a_life_event_type_in_another_account_is_not_found(): void
    {
        Queue::fake();

        $elsewhere = new CreateLifeEventType(
            user: $stranger = User::factory()->create(),
            lifeEventCategory: LifeEventCategory::factory()->create(['account_id' => $stranger->account_id]),
            name: 'Restaurant',
        )->execute();

        $this->assertInstanceOf(LifeEventType::class, $elsewhere);

        $mine = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateStructureVersionDraft(
            user: $mine,
            structureVersion: $elsewhere->structure->publishedVersion,
        )->execute();
    }

    private function publishedVersion(User $user, Template $template): StructureVersion
    {
        $version = StructureVersion::factory()->create([
            'account_id' => $user->account_id,
            'structure_id' => $template->structure->id,
        ]);

        new PublishStructureVersion(user: $user, structureVersion: $version)->execute();

        return $version->refresh();
    }

    private function publishAnotherVersion(User $user, StructureVersion $from): StructureVersion
    {
        $draft = new CreateStructureVersionDraft(user: $user, structureVersion: $from)->execute();

        new PublishStructureVersion(user: $user, structureVersion: $draft, changeNote: 'One more field.')->execute();

        return $draft->refresh();
    }
}
