<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyStructureVersionPage;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\CannotDeleteAPageInUse;
use App\Exceptions\CannotDeleteTheLastPage;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Jobs\LogUserAction;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyStructureVersionPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_a_page_off_a_structure_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $page = StructureVersionPage::factory()->create([
            'structure_version_id' => $structureVersion->id,
            'label' => 'Health',
        ]);

        new DestroyStructureVersionPage(user: $user, page: $page)->execute();

        $this->assertModelMissing($page);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::TemplatePageDeletion
                && $job->parameters['label'] === 'Health',
        );
    }

    #[Test]
    public function it_numbers_the_pages_left_from_one_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $general = $structureVersion->pages()->firstOrFail();
        $health = StructureVersionPage::factory()->create(['structure_version_id' => $structureVersion->id]);
        $work = StructureVersionPage::factory()->create(['structure_version_id' => $structureVersion->id]);

        new DestroyStructureVersionPage(user: $user, page: $health)->execute();

        $this->assertEquals(1, $general->refresh()->position);
        $this->assertEquals(2, $work->refresh()->position);
    }

    #[Test]
    public function it_refuses_to_take_the_last_page_of_a_structure_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $page = $structureVersion->pages()->firstOrFail();

        $this->expectException(CannotDeleteTheLastPage::class);

        new DestroyStructureVersionPage(user: $user, page: $page)->execute();
    }

    #[Test]
    public function it_refuses_to_take_a_page_that_still_holds_sections(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $page = StructureVersionPage::factory()->create(['structure_version_id' => $structureVersion->id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $structureVersion->id,
            'structure_version_page_id' => $page->id,
        ]);

        $this->expectException(CannotDeleteAPageInUse::class);

        new DestroyStructureVersionPage(user: $user, page: $page)->execute();
    }

    #[Test]
    public function it_refuses_a_page_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $page = StructureVersionPage::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyStructureVersionPage(user: $user, page: $page)->execute();
    }

    #[Test]
    public function it_refuses_to_take_a_page_off_a_published_structure_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $page = StructureVersionPage::factory()->create(['structure_version_id' => $structureVersion->id]);
        $structureVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $page->refresh();

        $this->expectException(PublishedVersionIsFrozen::class);

        new DestroyStructureVersionPage(user: $user, page: $page)->execute();
    }
}
