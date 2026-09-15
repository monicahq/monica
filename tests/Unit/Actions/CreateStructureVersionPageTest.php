<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateStructureVersionPage;
use App\Enums\UserActionEnum;
use App\Exceptions\PageLabelIsAlreadyUsed;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Jobs\LogUserAction;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateStructureVersionPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_page_to_a_structure_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);

        $page = new CreateStructureVersionPage(
            user: $user,
            structureVersion: $structureVersion,
            label: 'Health',
        )->execute();

        $this->assertInstanceOf(StructureVersionPage::class, $page);

        $this->assertDatabaseHas('structure_version_pages', [
            'account_id' => $user->account_id,
            'structure_version_id' => $structureVersion->id,
            'key' => 'health',
            'label' => 'Health',
            'label_translation_key' => null,
            'position' => 2,
        ]);
    }

    #[Test]
    public function it_puts_the_new_page_after_the_ones_already_there(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);

        $first = new CreateStructureVersionPage(user: $user, structureVersion: $structureVersion, label: 'Health')->execute();
        $second = new CreateStructureVersionPage(user: $user, structureVersion: $structureVersion, label: 'Work')->execute();

        $this->assertEquals(2, $first->position);
        $this->assertEquals(3, $second->position);
    }

    #[Test]
    public function it_takes_the_key_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);

        $page = new CreateStructureVersionPage(
            user: $user,
            structureVersion: $structureVersion,
            label: 'Health and safety',
            key: 'medical',
        )->execute();

        $this->assertEquals('medical', $page->key);
    }

    #[Test]
    public function it_counts_a_key_up_rather_than_repeating_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);

        $first = new CreateStructureVersionPage(user: $user, structureVersion: $structureVersion, label: 'Health')->execute();
        $second = new CreateStructureVersionPage(user: $user, structureVersion: $structureVersion, label: 'Health record', key: 'health')->execute();

        $this->assertEquals('health', $first->key);
        $this->assertEquals('health-2', $second->key);
    }

    #[Test]
    public function it_refuses_a_label_another_page_of_the_version_already_reads(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);

        new CreateStructureVersionPage(user: $user, structureVersion: $structureVersion, label: 'Health')->execute();

        $this->expectException(PageLabelIsAlreadyUsed::class);

        new CreateStructureVersionPage(user: $user, structureVersion: $structureVersion, label: 'health')->execute();
    }

    #[Test]
    public function it_refuses_a_structure_version_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateStructureVersionPage(user: $user, structureVersion: $structureVersion, label: 'Health')->execute();
    }

    #[Test]
    public function it_refuses_to_add_a_page_to_a_published_structure_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->published()->create(['account_id' => $user->account_id]);

        $this->expectException(PublishedVersionIsFrozen::class);

        new CreateStructureVersionPage(user: $user, structureVersion: $structureVersion, label: 'Health')->execute();
    }

    #[Test]
    public function it_logs_the_page_being_added(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);

        new CreateStructureVersionPage(user: $user, structureVersion: $structureVersion, label: 'Health')->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::TemplatePageCreation
                && $job->account->id === $user->account_id
                && $job->parameters['label'] === 'Health',
        );
    }
}
