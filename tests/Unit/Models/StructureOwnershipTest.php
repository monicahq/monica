<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Actions\CreateLifeEventType;
use App\Actions\CreateStructureVersionDraft;
use App\Actions\PublishStructureVersion;
use App\Enums\VersionStatusEnum;
use App\Exceptions\CannotPublishAnEmptyPage;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\StructureVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StructureOwnershipTest extends TestCase
{
    use RefreshDatabase;

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

    private function lifeEventType(User $user): LifeEventType
    {
        return new CreateLifeEventType(
            user: $user,
            lifeEventCategory: LifeEventCategory::factory()->create(['account_id' => $user->account_id]),
            name: 'Restaurant',
        )->execute();
    }
}
