<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\RecordKind;
use App\Helpers\RecordStatistics;
use App\Models\Contact;
use App\Models\Template;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Statistics\AnimalsVault;
use Tests\TestCase;

class RecordStatisticsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_counts_the_animals_of_the_vault_by_template_and_version(): void
    {
        $fixture = AnimalsVault::build();

        $statistics = new RecordStatistics(kind: RecordKind::forEntityType(vault: $fixture->vault, entityType: $fixture->animals))->toArray();

        $this->assertSame(['records' => 18, 'templatesInUse' => 2, 'onOlderVersions' => 3, 'archived' => 2, 'partial' => null], $statistics['overview']);
        $this->assertSame(['Pet', 'Horse', 'Bird'], array_map(fn (array $row): string => $row['template']->name, $statistics['templates']));

        [$pet, $horse, $bird] = $statistics['templates'];

        $this->assertTrue($pet['isDefault']);
        $this->assertSame([14, 78, 3], [$pet['records'], $pet['share'], $pet['publishedVersion']]);
        $this->assertSame([[3, 11], [2, 3]], array_map(fn (array $version): array => [$version['number'], $version['records']], $pet['versions']));
        $this->assertSame([4, 22], [$horse['records'], $horse['share']]);
        $this->assertSame([0, []], [$bird['records'], $bird['versions']]);
    }

    #[Test]
    public function it_keeps_a_turned_off_template_that_still_holds_records_and_drops_an_empty_one(): void
    {
        $fixture = AnimalsVault::build();
        $fixture->horse->update(['is_active' => false]);
        $fixture->bird->update(['is_active' => false]);

        $statistics = new RecordStatistics(kind: RecordKind::forEntityType(vault: $fixture->vault, entityType: $fixture->animals))->toArray();

        $this->assertSame(['Pet', 'Horse'], array_map(fn (array $row): string => $row['template']->name, $statistics['templates']));
        $this->assertFalse($statistics['templates'][1]['isActive']);
    }

    #[Test]
    public function it_counts_partial_contacts_in_the_total_and_apart(): void
    {
        $fixture = AnimalsVault::build();
        $template = Template::factory()->published()->create(['account_id' => $fixture->user->account_id, 'name' => 'Coworker']);
        $version = $template->structure->published_version_id;
        $attributes = ['account_id' => $fixture->user->account_id, 'vault_id' => $fixture->vault->id, 'structure_version_id' => $version];
        Contact::factory()->create([...$attributes, 'first_name' => 'Michael']);
        Contact::factory()->create([...$attributes, 'first_name' => 'Holly', 'is_partial' => true]);
        Contact::factory()->create([...$attributes, 'first_name' => 'Jan', 'is_archived' => true]);

        $statistics = new RecordStatistics(kind: RecordKind::forContacts(vault: $fixture->vault))->toArray();

        $this->assertSame(['records' => 2, 'templatesInUse' => 1, 'onOlderVersions' => 0, 'archived' => 1, 'partial' => 1], $statistics['overview']);
    }

    #[Test]
    public function it_gives_zeros_and_every_offered_template_for_an_empty_type(): void
    {
        $fixture = AnimalsVault::build();
        $emptyVault = Vault::factory()->create(['account_id' => $fixture->user->account_id]);

        $statistics = new RecordStatistics(kind: RecordKind::forEntityType(vault: $emptyVault, entityType: $fixture->animals))->toArray();

        $this->assertSame(['records' => 0, 'templatesInUse' => 0, 'onOlderVersions' => 0, 'archived' => 0, 'partial' => null], $statistics['overview']);
        $this->assertCount(3, $statistics['templates']);
        $this->assertSame(0, $statistics['templates'][0]['share']);
    }
}
