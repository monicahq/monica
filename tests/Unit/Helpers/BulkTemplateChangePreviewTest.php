<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\BulkTemplateChangePreview;
use App\Helpers\RecordKind;
use App\Helpers\TemplateChangeBatch;
use App\Models\Entity;
use App\Models\FieldValue;
use App\Models\TemplateChange;
use App\Models\TemplateChangeItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Statistics\AnimalsVault;
use Tests\TestCase;

class BulkTemplateChangePreviewTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_counts_what_moving_the_horses_onto_pet_would_do(): void
    {
        $fixture = AnimalsVault::build();

        $preview = $this->preview($fixture, namesSensitiveFields: true);

        $this->assertSame(['ready' => 4, 'unchanged' => 2, 'setAside' => 2, 'restored' => 0, 'remindersPaused' => 1, 'remindersResumed' => 0], $preview['counts']);
        $this->assertSame([
            ['label' => 'Stable name', 'section' => 'Stable', 'records' => 2],
            ['label' => 'Farrier visit', 'section' => 'Stable', 'records' => 1],
        ], $preview['setAside']);
        $this->assertSame([['name' => 'Farrier visit', 'records' => 1]], $preview['remindersPaused']);
        $this->assertSame([], $preview['blocked']);
    }

    #[Test]
    public function it_counts_values_coming_back_from_an_earlier_change(): void
    {
        $fixture = AnimalsVault::build();
        $pepper = Entity::query()->where('name', 'Pepper')->firstOrFail();
        FieldValue::factory()->create([
            'account_id' => $pepper->account_id,
            'vault_id' => $pepper->vault_id,
            'valuable_type' => Entity::class,
            'valuable_id' => $pepper->id,
            'field_id' => $fixture->fields['microchip']->id,
            'value_string' => '250999',
            'is_detached' => true,
        ]);

        $preview = $this->preview($fixture, namesSensitiveFields: true, source: 'pet', versions: [2]);

        $this->assertSame(3, $preview['counts']['ready']);
        $this->assertSame(1, $preview['counts']['restored']);
        $this->assertSame([['label' => 'Microchip', 'section' => 'Identification', 'records' => 1]], $preview['restored']);
    }

    #[Test]
    public function it_counts_a_sensitive_field_without_naming_it(): void
    {
        $fixture = AnimalsVault::build();
        $fixture->fields['stable_name']->updateQuietly(['is_sensitive' => true]);

        $preview = $this->preview($fixture, namesSensitiveFields: false);

        $this->assertSame(2, $preview['counts']['setAside']);
        $this->assertSame(['Farrier visit'], array_column($preview['setAside'], 'label'));
        $this->assertSame(1, $preview['hiddenCount']);
    }

    #[Test]
    public function it_blocks_a_record_another_running_change_holds(): void
    {
        $fixture = AnimalsVault::build();
        $change = TemplateChange::factory()->create(['vault_id' => $fixture->vault->id]);
        TemplateChangeItem::factory()->create([
            'template_change_id' => $change->id,
            'record_type' => Entity::class,
            'record_id' => $fixture->horses['Bijou']->id,
            'from_version_id' => $fixture->horseV1->id,
        ]);

        $preview = $this->preview($fixture, namesSensitiveFields: true);

        $this->assertSame([$fixture->horses['Bijou']->id], $preview['blocked']);
        $this->assertSame(3, $preview['counts']['ready']);
    }

    #[Test]
    public function it_changes_nothing(): void
    {
        $fixture = AnimalsVault::build();
        $before = FieldValue::query()->orderBy('id')->get(['id', 'field_id', 'is_detached'])->toArray();

        $this->preview($fixture, namesSensitiveFields: true);

        $this->assertSame($before, FieldValue::query()->orderBy('id')->get(['id', 'field_id', 'is_detached'])->toArray());
        $this->assertSame(4, Entity::query()->where('structure_version_id', $fixture->horseV1->id)->count());
    }

    /**
     * @param  list<int>  $versions
     * @return array<string, mixed>
     */
    private function preview(AnimalsVault $fixture, bool $namesSensitiveFields, string $source = 'horse', array $versions = []): array
    {
        return new BulkTemplateChangePreview(
            batch: new TemplateChangeBatch(
                kind: RecordKind::forEntityType(vault: $fixture->vault, entityType: $fixture->animals),
                source: $fixture->{$source},
                versionNumbers: $versions,
                target: $fixture->petV3,
            ),
            namesSensitiveFields: $namesSensitiveFields,
        )->toArray();
    }
}
