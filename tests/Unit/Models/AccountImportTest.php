<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\AccountImportOutcomeEnum;
use App\Models\AccountImport;
use App\Models\AccountImportItem;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountImportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_holds_several_vaults_when_the_file_has_two(): void
    {
        $import = AccountImport::factory()->create();
        AccountImportItem::factory()->count(2)->create(['account_import_id' => $import->id, 'kind' => 'vault']);

        $this->assertTrue($import->holdsSeveralVaults());
    }

    #[Test]
    public function it_adds_up_what_its_items_wrote_inside_them_leaving_out_the_items_refused(): void
    {
        $import = AccountImport::factory()->create();
        AccountImportItem::factory()->create(['account_import_id' => $import->id, 'kind' => 'contact', 'outcome' => AccountImportOutcomeEnum::Imported, 'payload' => ['written' => ['notes' => 2, 'gifts' => 1]]]);
        AccountImportItem::factory()->create(['account_import_id' => $import->id, 'kind' => 'contact', 'outcome' => AccountImportOutcomeEnum::MergedIntoMe, 'payload' => ['values' => [], 'written' => ['notes' => 1]]]);
        AccountImportItem::factory()->create(['account_import_id' => $import->id, 'kind' => 'contact', 'outcome' => AccountImportOutcomeEnum::LeftOut, 'payload' => ['written' => ['notes' => 5]]]);
        AccountImportItem::factory()->create(['account_import_id' => $import->id, 'kind' => 'contact', 'outcome' => AccountImportOutcomeEnum::Imported, 'payload' => null]);

        $this->assertEquals(['notes' => 3, 'gifts' => 1], $import->writtenInsideItems());
    }

    #[Test]
    public function it_does_not_hold_several_vaults_when_the_file_has_one(): void
    {
        $import = AccountImport::factory()->create();
        AccountImportItem::factory()->create(['account_import_id' => $import->id, 'kind' => 'vault']);
        AccountImportItem::factory()->count(3)->create(['account_import_id' => $import->id, 'kind' => 'contact']);

        $this->assertFalse($import->holdsSeveralVaults());
    }

    #[Test]
    public function it_names_the_vault_chosen_for_a_file_of_one_vault(): void
    {
        $scranton = Vault::factory()->create(['name' => 'Scranton']);
        $import = AccountImport::factory()->create(['vault_id' => $scranton->id]);

        $this->assertSame([$scranton->id], $import->vaultIds());
    }

    #[Test]
    public function it_names_a_new_vault_chosen_for_a_file_of_one_vault_once(): void
    {
        $scranton = Vault::factory()->create(['name' => 'Scranton']);
        $import = AccountImport::factory()->create(['vault_id' => $scranton->id, 'created_vault_ids' => [$scranton->id]]);

        $this->assertSame([$scranton->id], $import->vaultIds());
    }

    #[Test]
    public function it_names_every_vault_created_for_a_file_of_several(): void
    {
        $scranton = Vault::factory()->create(['name' => 'Scranton']);
        $stamford = Vault::factory()->create(['name' => 'Stamford']);
        $import = AccountImport::factory()->create(['created_vault_ids' => [$scranton->id, $stamford->id]]);

        $this->assertSame([$scranton->id, $stamford->id], $import->vaultIds());
    }

    #[Test]
    public function it_names_no_vault_before_one_is_chosen(): void
    {
        $import = AccountImport::factory()->create();

        $this->assertSame([], $import->vaultIds());
    }
}
