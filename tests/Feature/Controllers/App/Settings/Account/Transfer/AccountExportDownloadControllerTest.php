<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Transfer;

use App\Enums\AccountExportStatusEnum;
use App\Models\AccountExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountExportDownloadControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $pam;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Queue::fake();

        $this->pam = User::factory()->create();
    }

    #[Test]
    public function it_hands_the_file_to_its_account_holder(): void
    {
        $export = AccountExport::factory()->create([
            'user_id' => $this->pam->id,
            'status' => AccountExportStatusEnum::Ready,
            'path' => "accounts/{$this->pam->account_id}/transfers/dundies/monica-export-2026-10-18.json",
            'ready_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);
        Storage::disk('local')->put($export->path, '{"version":"2.0-preview.1"}');

        $response = $this->actingAs($this->pam)->withConfirmedPassword()->get(route('settings.accountExportDownloads.show', $export));

        $response->assertOk()->assertDownload('monica-export-2026-10-18.json');
        $this->assertSame('{"version":"2.0-preview.1"}', $response->streamedContent());
    }

    #[Test]
    public function it_answers_not_found_for_an_export_still_being_prepared(): void
    {
        $export = AccountExport::factory()->create(['user_id' => $this->pam->id, 'status' => AccountExportStatusEnum::Preparing]);
        Storage::disk('local')->put($export->path, '{"version":"2.0-preview.1"');

        $this->actingAs($this->pam)->withConfirmedPassword()
            ->get(route('settings.accountExportDownloads.show', $export))
            ->assertNotFound();
    }
}
