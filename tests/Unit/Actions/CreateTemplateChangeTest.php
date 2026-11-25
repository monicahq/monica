<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateTemplateChange;
use App\Enums\UserActionEnum;
use App\Helpers\RecordKind;
use App\Jobs\ChangeTemplateOfRecords;
use App\Jobs\LogUserAction;
use App\Models\Entity;
use App\Models\Template;
use App\Models\TemplateChange;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Statistics\AnimalsVault;
use Tests\TestCase;

class CreateTemplateChangeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_the_batch_and_hands_it_to_the_queue(): void
    {
        Queue::fake();
        $fixture = AnimalsVault::build();

        $change = $this->change($fixture, source: $fixture->horse, target: $fixture->pet);

        $this->assertInstanceOf(TemplateChange::class, $change);
        $this->assertDatabaseHas('template_changes', ['id' => $change->id, 'from_template_id' => $fixture->horse->id, 'structure_version_id' => $fixture->petV3->id, 'items_total' => 4, 'ended_at' => null]);
        $this->assertSame(
            collect($fixture->horses)->pluck('id')->sort()->values()->all(),
            $change->items()->orderBy('record_id')->pluck('record_id')->all(),
        );
        Queue::assertPushed(ChangeTemplateOfRecords::class, 1);
        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::TemplateChangeStarted
                && $job->parameters === ['count' => 4, 'template' => 'Pet'],
        );
    }

    #[Test]
    public function it_takes_only_the_older_versions_when_updating_to_the_published_one(): void
    {
        Queue::fake();
        $fixture = AnimalsVault::build();

        $change = $this->change($fixture, source: $fixture->pet, target: $fixture->pet);

        $this->assertSame(3, $change->items_total);
        $this->assertSame([$fixture->petV2->id], $change->items()->distinct()->pluck('from_version_id')->all());
    }

    #[Test]
    public function it_leaves_archived_records_out(): void
    {
        Queue::fake();
        $fixture = AnimalsVault::build();
        $fixture->horses['Bijou']->update(['is_archived' => true]);

        $change = $this->change($fixture, source: $fixture->horse, target: $fixture->pet);

        $this->assertSame(3, $change->items_total);
    }

    #[Test]
    public function it_refuses_a_target_that_is_turned_off(): void
    {
        $fixture = AnimalsVault::build();
        $fixture->pet->update(['is_active' => false]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This template is turned off, so no record can be put on it.');

        $this->change($fixture, source: $fixture->horse, target: $fixture->pet);
    }

    #[Test]
    public function it_refuses_a_target_describing_another_kind_of_record(): void
    {
        $fixture = AnimalsVault::build();
        $coworker = Template::factory()->published()->create(['account_id' => $fixture->user->account_id]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This template describes another kind of record.');

        $this->change($fixture, source: $fixture->horse, target: $coworker);
    }

    #[Test]
    public function it_refuses_a_batch_with_nothing_to_move(): void
    {
        $fixture = AnimalsVault::build();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No record is left to move.');

        $this->change($fixture, source: $fixture->bird, target: $fixture->pet);
    }

    #[Test]
    public function it_refuses_a_second_batch_for_records_already_waiting_to_move(): void
    {
        Queue::fake();
        $fixture = AnimalsVault::build();
        $this->change($fixture, source: $fixture->horse, target: $fixture->pet);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No record is left to move.');

        $this->change($fixture, source: $fixture->horse, target: $fixture->pet);
    }

    #[Test]
    public function it_refuses_a_template_of_another_account(): void
    {
        $fixture = AnimalsVault::build();
        $stranger = AnimalsVault::build();

        $this->expectException(ModelNotFoundException::class);

        $this->change($fixture, source: $fixture->horse, target: $stranger->pet);
    }

    #[Test]
    public function it_refuses_a_vault_of_another_account(): void
    {
        $fixture = AnimalsVault::build();
        $stranger = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateTemplateChange(
            user: $stranger,
            kind: RecordKind::forEntityType(vault: $fixture->vault, entityType: $fixture->animals),
            source: $fixture->horse,
            versionNumbers: [],
            target: $fixture->pet,
        )->execute();
    }

    #[Test]
    public function it_splits_a_large_batch_into_slices(): void
    {
        Queue::fake();
        $fixture = AnimalsVault::build();
        Entity::factory()->count(ChangeTemplateOfRecords::SIZE)->create([
            'account_id' => $fixture->user->account_id,
            'vault_id' => $fixture->vault->id,
            'entity_type_id' => $fixture->animals->id,
            'structure_version_id' => $fixture->horseV1->id,
        ]);

        $this->change($fixture, source: $fixture->horse, target: $fixture->pet);

        Queue::assertPushed(ChangeTemplateOfRecords::class, 2);
    }

    private function change(AnimalsVault $fixture, Template $source, Template $target): TemplateChange
    {
        return new CreateTemplateChange(
            user: $fixture->user,
            kind: RecordKind::forEntityType(vault: $fixture->vault, entityType: $fixture->animals),
            source: $source,
            versionNumbers: [],
            target: $target,
        )->execute();
    }
}
