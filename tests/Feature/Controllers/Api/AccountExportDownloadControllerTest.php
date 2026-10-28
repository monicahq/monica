<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

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

    private User $michael;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Queue::fake();

        $this->michael = User::factory()->create();
    }

    #[Test]
    public function it_hands_over_the_file_of_a_ready_export(): void
    {
        $export = $this->readyExportOf($this->michael);

        $response = $this->withToken($this->michael->createToken('Michael’s script', ['sensitive-fields'])->plainTextToken)
            ->get("/api/v1/exports/{$export->uuid}/download");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertDownload('monica-export-2026-10-18.json');
        $this->assertSame('{"version": "2.0-preview.1"}', $response->streamedContent());
    }

    #[Test]
    public function it_refuses_a_key_without_the_sensitive_fields_scope(): void
    {
        $export = $this->readyExportOf($this->michael);

        $this->withToken($this->michael->createToken('Michael’s script')->plainTextToken)
            ->get("/api/v1/exports/{$export->uuid}/download")
            ->assertForbidden();
    }

    #[Test]
    public function it_answers_not_found_for_an_export_that_is_not_ready(): void
    {
        $export = $this->readyExportOf($this->michael);
        $export->update(['status' => AccountExportStatusEnum::Preparing]);

        $this->withToken($this->michael->createToken('Michael’s script', ['sensitive-fields'])->plainTextToken)
            ->get("/api/v1/exports/{$export->uuid}/download")
            ->assertNotFound();
    }

    #[Test]
    public function it_answers_not_found_for_an_export_of_another_account(): void
    {
        $export = $this->readyExportOf(User::factory()->create());

        $this->withToken($this->michael->createToken('Michael’s script', ['sensitive-fields'])->plainTextToken)
            ->get("/api/v1/exports/{$export->uuid}/download")
            ->assertNotFound();
    }

    private function readyExportOf(User $user): AccountExport
    {
        $export = AccountExport::factory()->create([
            'user_id' => $user->id,
            'status' => AccountExportStatusEnum::Ready,
            'path' => "accounts/{$user->account_id}/transfers/dunder-mifflin/monica-export-2026-10-18.json",
            'size_in_bytes' => 40,
            'ready_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);
        Storage::disk('local')->put($export->path, '{"version": "2.0-preview.1"}');

        return $export;
    }
}
