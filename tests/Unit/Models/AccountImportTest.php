<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\AccountImportOutcomeEnum;
use App\Models\AccountImport;
use App\Models\AccountImportItem;
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
}
