<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Transfer;

use App\Enums\AccountExportStatusEnum;
use App\Enums\AccountImportStatusEnum;
use App\Helpers\StorageSize;
use App\Models\AccountExport;
use App\Models\AccountImport;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Settings\Account\Transfer\AccountTransferViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountTransferViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_the_download_of_a_ready_export_until_it_expires(): void
    {
        $pam = User::factory()->create();
        $scranton = Vault::factory()->create(['account_id' => $pam->account_id, 'name' => 'Scranton']);
        $nashua = Vault::factory()->create(['account_id' => $pam->account_id, 'name' => 'Nashua']);
        $ready = AccountExport::factory()->create([
            'user_id' => $pam->id,
            'vault_ids' => [$scranton->id, $nashua->id],
            'status' => AccountExportStatusEnum::Ready,
            'size_in_bytes' => 48211,
            'ready_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);
        AccountExport::factory()->create([
            'user_id' => $pam->id,
            'vault_ids' => [$scranton->id],
            'status' => AccountExportStatusEnum::Ready,
            'size_in_bytes' => 1024,
            'ready_at' => now()->subDays(8),
            'expires_at' => now()->subDay(),
        ]);

        [$expiredRow, $readyRow] = new AccountTransferViewModel(user: $pam)->exports();

        $this->assertSame('Scranton, Nashua', $readyRow['vaults']);
        $this->assertSame('Ready to download', $readyRow['status']);
        $this->assertSame('success', $readyRow['tone']);
        $this->assertSame(StorageSize::readable(48211), $readyRow['size']);
        $this->assertSame(route('settings.accountExportDownloads.show', $ready), $readyRow['download_url']);
        $this->assertNull($expiredRow['download_url']);
    }

    #[Test]
    public function it_says_what_became_of_an_export_without_a_file(): void
    {
        $pam = User::factory()->create();
        AccountExport::factory()->create(['user_id' => $pam->id, 'vault_ids' => [], 'status' => AccountExportStatusEnum::Failed]);
        AccountExport::factory()->create(['user_id' => $pam->id, 'vault_ids' => [9999], 'status' => AccountExportStatusEnum::Preparing]);
        AccountExport::factory()->create();

        [$preparing, $failed] = new AccountTransferViewModel(user: $pam)->exports();

        $this->assertSame('Being prepared', $preparing['status']);
        $this->assertSame('A vault since deleted', $preparing['vaults']);
        $this->assertNull($preparing['size']);
        $this->assertSame('Failed', $failed['status']);
        $this->assertSame('danger', $failed['tone']);
        $this->assertNull($failed['download_url']);
    }

    #[Test]
    public function it_offers_the_vaults_of_the_account_to_export_and_says_why_another_export_cannot_start(): void
    {
        $pam = User::factory()->create();
        Vault::factory()->create(['account_id' => $pam->account_id, 'name' => 'Nashua', 'position' => 2]);
        Vault::factory()->create(['account_id' => $pam->account_id, 'name' => 'Scranton', 'position' => 1]);
        Vault::factory()->create(['name' => 'Stamford']);

        $viewModel = new AccountTransferViewModel(user: $pam);

        $this->assertSame(['Scranton', 'Nashua'], array_column($viewModel->vaults(), 'name'));
        $this->assertNull($viewModel->exportRefusal());

        AccountExport::factory()->create(['user_id' => $pam->id, 'status' => AccountExportStatusEnum::Preparing]);

        $this->assertSame('An export is already being prepared.', $viewModel->exportRefusal());
    }

    #[Test]
    public function it_knows_when_an_export_of_the_account_is_being_prepared(): void
    {
        $pam = User::factory()->create();
        AccountExport::factory()->create(['status' => AccountExportStatusEnum::Preparing]);
        AccountExport::factory()->create(['user_id' => $pam->id, 'status' => AccountExportStatusEnum::Failed]);
        $viewModel = new AccountTransferViewModel(user: $pam);

        $this->assertFalse($viewModel->isPreparingAnExport());

        AccountExport::factory()->create(['user_id' => $pam->id, 'status' => AccountExportStatusEnum::Preparing]);

        $this->assertTrue($viewModel->isPreparingAnExport());
    }

    #[Test]
    public function it_says_why_another_file_cannot_be_read_yet(): void
    {
        $pam = User::factory()->create();
        $viewModel = new AccountTransferViewModel(user: $pam);

        $this->assertNull($viewModel->importRefusal());

        AccountImport::factory()->create(['user_id' => $pam->id, 'status' => AccountImportStatusEnum::Read]);

        $this->assertSame('A file has been read and waits for you to start it or discard it.', $viewModel->importRefusal());
    }
}
