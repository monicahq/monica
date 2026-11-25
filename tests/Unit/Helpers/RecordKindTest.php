<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\RecordKind;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Template;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Statistics\AnimalsVault;
use Tests\TestCase;

class RecordKindTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_resolves_people_and_an_entity_type_from_the_api_segment(): void
    {
        $fixture = AnimalsVault::build();

        $people = RecordKind::fromApiSegment(vault: $fixture->vault, segment: 'contacts');
        $animals = RecordKind::fromApiSegment(vault: $fixture->vault, segment: $fixture->animals->uuid);

        $this->assertTrue($people->isPeople());
        $this->assertSame('Animals', $animals->plural());
        $this->assertSame($fixture->animals->uuid, $animals->apiSegment());
    }

    #[Test]
    public function it_refuses_an_entity_type_of_another_account(): void
    {
        $fixture = AnimalsVault::build();
        $elsewhere = EntityType::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        RecordKind::fromApiSegment(vault: $fixture->vault, segment: $elsewhere->uuid);
    }

    #[Test]
    public function it_queries_only_the_records_of_its_vault_and_type(): void
    {
        $fixture = AnimalsVault::build();
        $otherVault = Vault::factory()->create(['account_id' => $fixture->user->account_id]);
        Entity::factory()->create(['vault_id' => $otherVault->id, 'entity_type_id' => $fixture->animals->id, 'structure_version_id' => $fixture->petV3->id]);
        $plants = EntityType::factory()->create(['account_id' => $fixture->user->account_id]);
        Entity::factory()->create(['vault_id' => $fixture->vault->id, 'entity_type_id' => $plants->id]);

        $kind = RecordKind::forEntityType(vault: $fixture->vault, entityType: $fixture->animals);

        $this->assertSame(20, $kind->recordQuery()->count());
    }

    #[Test]
    public function it_lists_the_templates_of_its_kind_and_names_the_default(): void
    {
        $fixture = AnimalsVault::build();
        Template::factory()->published()->create(['account_id' => $fixture->user->account_id, 'name' => 'Coworker']);

        $kind = RecordKind::forEntityType(vault: $fixture->vault, entityType: $fixture->animals);

        $this->assertSame(['Pet', 'Horse', 'Bird'], $kind->templates()->pluck('name')->all());
        $this->assertSame($fixture->pet->id, $kind->defaultTemplateId());
        $this->assertFalse($kind->describes(Template::query()->where('name', 'Coworker')->firstOrFail()));
    }
}
