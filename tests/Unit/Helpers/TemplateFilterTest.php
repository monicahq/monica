<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Actions\CreateTemplateChange;
use App\Helpers\RecordKind;
use App\Helpers\TemplateFilter;
use App\Jobs\ChangeTemplateOfRecords;
use App\Models\Entity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Statistics\AnimalsVault;
use Tests\TestCase;

class TemplateFilterTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_narrows_a_list_to_a_template_and_then_to_one_of_its_versions(): void
    {
        $fixture = AnimalsVault::build();
        $kind = RecordKind::forEntityType(vault: $fixture->vault, entityType: $fixture->animals);

        $byTemplate = TemplateFilter::fromValidated(kind: $kind, template: $fixture->pet->uuid, versionNumber: null);
        $byVersion = TemplateFilter::fromValidated(kind: $kind, template: $fixture->pet->uuid, versionNumber: 2);

        $this->assertSame(16, $byTemplate->apply(Entity::query()->where('vault_id', $fixture->vault->id))->count());
        $this->assertSame(['Nougat', 'Pepper', 'Ziggy'], $byVersion->apply(Entity::query())->orderBy('name')->pluck('name')->all());
        $this->assertSame(['Template: Pet', 'Version: v2'], array_column($byVersion->chips(), 'label'));
        $this->assertSame(['template' => $fixture->pet->uuid, 'version' => null, 'change' => null], $byVersion->chips()[1]['parameters']);
    }

    #[Test]
    public function it_ignores_a_template_of_another_kind(): void
    {
        $fixture = AnimalsVault::build();

        $filter = TemplateFilter::fromValidated(kind: RecordKind::forContacts(vault: $fixture->vault), template: $fixture->pet->uuid, versionNumber: null);

        $this->assertFalse($filter->isActive());
    }

    #[Test]
    public function it_narrows_a_list_to_the_records_a_change_of_template_moved(): void
    {
        Queue::fake();
        $fixture = AnimalsVault::build();
        $kind = RecordKind::forEntityType(vault: $fixture->vault, entityType: $fixture->animals);
        $change = new CreateTemplateChange(user: $fixture->user, kind: $kind, source: $fixture->horse, versionNumbers: [], target: $fixture->pet)->execute();
        $fixture->horses['Bijou']->update(['structure_version_id' => $fixture->birdV1->id]);
        $ids = $change->items()->orderBy('id')->pluck('id');
        new ChangeTemplateOfRecords(templateChange: $change, firstItemId: $ids->first(), lastItemId: $ids->last())->handle();

        $filter = TemplateFilter::fromValidated(kind: $kind, template: null, versionNumber: null, change: $change->uuid);

        $this->assertSame(['Comète', 'Tornado', 'Ulysse'], $filter->apply(Entity::query())->orderBy('name')->pluck('name')->all());
        $this->assertSame(['Moved by a change of template'], array_column($filter->chips(), 'label'));
        $this->assertSame(['template' => null, 'version' => null, 'change' => null], $filter->chips()[0]['parameters']);
    }

    #[Test]
    public function it_ignores_a_change_of_template_of_another_kind(): void
    {
        Queue::fake();
        $fixture = AnimalsVault::build();
        $change = new CreateTemplateChange(
            user: $fixture->user,
            kind: RecordKind::forEntityType(vault: $fixture->vault, entityType: $fixture->animals),
            source: $fixture->horse,
            versionNumbers: [],
            target: $fixture->pet,
        )->execute();

        $filter = TemplateFilter::fromValidated(kind: RecordKind::forContacts(vault: $fixture->vault), template: null, versionNumber: null, change: $change->uuid);

        $this->assertFalse($filter->isActive());
    }
}
