<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Actions\CreateTemplateChange;
use App\Helpers\RecordKind;
use App\Jobs\ChangeTemplateOfRecords;
use App\ViewModels\Vaults\TemplateChangeViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Statistics\AnimalsVault;
use Tests\TestCase;

class TemplateChangeViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sums_up_a_partial_result_with_the_reason_for_each_record_left_behind(): void
    {
        Queue::fake();
        $fixture = AnimalsVault::build();
        $kind = RecordKind::forEntityType(vault: $fixture->vault, entityType: $fixture->animals);
        $change = new CreateTemplateChange(user: $fixture->user, kind: $kind, source: $fixture->horse, versionNumbers: [], target: $fixture->pet)->execute();
        $fixture->horses['Bijou']->update(['structure_version_id' => $fixture->birdV1->id]);
        $ids = $change->items()->orderBy('id')->pluck('id');

        $running = new TemplateChangeViewModel(user: $fixture->user, kind: $kind, templateChange: $change);
        $this->assertTrue($running->isRunning());
        $this->assertSame('0 of 4 Animals dealt with so far.', $running->progressSentence());

        new ChangeTemplateOfRecords(templateChange: $change, firstItemId: $ids->first(), lastItemId: $ids->last())->handle();
        $finished = new TemplateChangeViewModel(user: $fixture->user, kind: $kind, templateChange: $change->refresh());

        $this->assertSame('Horse → Pet v3', $finished->title());
        $this->assertSame('3 Animals moved · 1 skipped', $finished->summary());
        $this->assertSame('Statistics now count the 3 Animals that moved.', $finished->recountSentence());
        $this->assertSame(route('entities.index', ['vault' => $fixture->vault, 'entityType' => $fixture->animals, 'change' => $change->uuid]), $finished->viewAffectedUrl());
        $this->assertSame([['name' => 'Bijou', 'outcome' => 'Skipped', 'reason' => 'This Animal’s template changed after the preview.']], $finished->notMoved());
    }
}
