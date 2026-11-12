<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateLifeEventType;
use App\Actions\CreateStructureVersionDraft;
use App\Actions\PublishStructureVersion;
use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\LifeEventCategory;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
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
