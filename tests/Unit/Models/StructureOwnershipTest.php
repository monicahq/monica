<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Actions\CreateLifeEventType;
use App\Actions\CreateStructureVersionDraft;
use App\Actions\CreateTemplate;
use App\Actions\PublishStructureVersion;
use App\Enums\StructureAppliesToEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\CannotPublishAnEmptyPage;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\Structure;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StructureOwnershipTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_template_and_a_life_event_type_both_own_a_structure(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = new CreateTemplate(user: $user, name: 'Contact')->execute();
        $lifeEventType = $this->lifeEventType($user);

        $this->assertEquals(Template::class, $template->structure->owner_type);
        $this->assertEquals(LifeEventType::class, $lifeEventType->structure->owner_type);
        $this->assertEquals(StructureAppliesToEnum::Contact, $template->structure->applies_to);
        $this->assertEquals(StructureAppliesToEnum::LifeEvent, $lifeEventType->structure->applies_to);
    }

    #[Test]
    public function a_structure_names_the_owner_it_belongs_to(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = new CreateTemplate(user: $user, name: 'Contact')->execute();
        $lifeEventType = $this->lifeEventType($user);

        $this->assertTrue($template->structure->owner->is($template));
        $this->assertTrue($lifeEventType->structure->owner->is($lifeEventType));
    }

    #[Test]
    public function both_kinds_publish_through_the_same_action(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = new CreateTemplate(user: $user, name: 'Contact')->execute();

        new PublishStructureVersion(user: $user, structureVersion: $template->versions()->sole())->execute();

        $lifeEventType = $this->lifeEventType($user);

        foreach ([$template->structure, $lifeEventType->structure] as $structure) {
            $this->assertEquals(
                VersionStatusEnum::Published,
                $structure->refresh()->publishedVersion->status,
            );
        }
    }

    #[Test]
    public function both_kinds_refuse_to_publish_a_version_with_no_page(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $lifeEventType = $this->lifeEventType($user);

        $version = StructureVersion::factory()->create([
            'account_id' => $user->account_id,
            'structure_id' => $lifeEventType->structure->id,
            'version_number' => 2,
        ]);
        $version->pages()->delete();

        $this->expectException(CannotPublishAnEmptyPage::class);

        new PublishStructureVersion(user: $user, structureVersion: $version, changeNote: 'Nothing on it.')->execute();
    }

    #[Test]
    public function editing_a_published_life_event_type_structure_makes_a_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $lifeEventType = $this->lifeEventType($user);
        $published = $lifeEventType->structure->publishedVersion;

        $draft = new CreateStructureVersionDraft(user: $user, structureVersion: $published)->execute();

        $this->assertEquals(VersionStatusEnum::Draft, $draft->status);
        $this->assertEquals(2, $draft->version_number);
        $this->assertEquals($published->id, $draft->previous_version_id);
        $this->assertEquals($published->id, $lifeEventType->structure->refresh()->published_version_id);
    }

    #[Test]
    public function publishing_a_later_version_leaves_the_earlier_one_where_it_is(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $lifeEventType = $this->lifeEventType($user);
        $first = $lifeEventType->structure->publishedVersion;

        $draft = new CreateStructureVersionDraft(user: $user, structureVersion: $first)->execute();
        new PublishStructureVersion(user: $user, structureVersion: $draft, changeNote: 'A venue.')->execute();

        $this->assertEquals(VersionStatusEnum::Published, $first->refresh()->status);
        $this->assertEquals($draft->id, $lifeEventType->structure->refresh()->published_version_id);
    }

    #[Test]
    public function a_structure_belonging_to_another_account_is_not_found(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $elsewhere = $this->lifeEventType(User::factory()->create());

        $this->expectException(ModelNotFoundException::class);

        new PublishStructureVersion(
            user: $user,
            structureVersion: $elsewhere->structure->versions()->sole(),
            changeNote: 'Not mine.',
        )->execute();
    }

    #[Test]
    public function an_owner_cannot_hold_two_structures(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $lifeEventType = $this->lifeEventType($user);

        $this->expectException(QueryException::class);

        Structure::query()->create([
            'account_id' => $user->account_id,
            'owner_type' => LifeEventType::class,
            'owner_id' => $lifeEventType->id,
            'applies_to' => StructureAppliesToEnum::LifeEvent,
            'entity_type_id' => null,
            'published_version_id' => null,
        ]);
    }

    private function lifeEventType(User $user): LifeEventType
    {
        return new CreateLifeEventType(
            user: $user,
            lifeEventCategory: LifeEventCategory::factory()->create(['account_id' => $user->account_id]),
            name: 'Restaurant',
        )->execute();
    }
}
