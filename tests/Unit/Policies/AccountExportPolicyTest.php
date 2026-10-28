<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\AccountExportStatusEnum;
use App\Models\AccountExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountExportPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_an_export_when_none_is_being_prepared(): void
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        AccountExport::factory()->create(['user_id' => $michael->id, 'status' => AccountExportStatusEnum::Ready]);
        AccountExport::factory()->create(['user_id' => $michael->id, 'status' => AccountExportStatusEnum::Failed]);
        AccountExport::factory()->create(['user_id' => $michael->id, 'status' => AccountExportStatusEnum::Expired]);

        $this->assertTrue(Gate::forUser($michael)->allows('create', AccountExport::class));
    }

    #[Test]
    public function it_refuses_an_export_while_one_is_being_prepared(): void
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        AccountExport::factory()->create(['user_id' => $michael->id, 'status' => AccountExportStatusEnum::Preparing]);

        $response = Gate::forUser($michael)->inspect('create', AccountExport::class);

        $this->assertTrue($response->denied());
        $this->assertSame('An export is already being prepared.', $response->message());
    }

    #[Test]
    public function it_allows_an_export_while_another_account_prepares_one(): void
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        $jan = User::factory()->create(['first_name' => 'Jan', 'last_name' => 'Levinson']);
        AccountExport::factory()->create(['user_id' => $jan->id, 'status' => AccountExportStatusEnum::Preparing]);

        $this->assertTrue(Gate::forUser($michael)->allows('create', AccountExport::class));
    }

    #[Test]
    public function it_allows_the_download_of_a_ready_export_before_it_expires(): void
    {
        $pam = User::factory()->create(['first_name' => 'Pam', 'last_name' => 'Beesly']);
        $export = AccountExport::factory()->create([
            'user_id' => $pam->id,
            'status' => AccountExportStatusEnum::Ready,
            'expires_at' => now()->addDay(),
        ]);

        $this->assertTrue(Gate::forUser($pam)->allows('download', $export));
    }

    #[Test]
    public function it_answers_not_found_for_the_download_of_an_export_not_ready_or_expired(): void
    {
        $pam = User::factory()->create(['first_name' => 'Pam', 'last_name' => 'Beesly']);
        $preparing = AccountExport::factory()->create(['user_id' => $pam->id, 'status' => AccountExportStatusEnum::Preparing]);
        $expired = AccountExport::factory()->create([
            'user_id' => $pam->id,
            'status' => AccountExportStatusEnum::Ready,
            'expires_at' => now()->subMinute(),
        ]);

        foreach ([$preparing, $expired] as $export) {
            $response = Gate::forUser($pam)->inspect('download', $export);

            $this->assertTrue($response->denied());
            $this->assertSame(404, $response->status());
        }
    }

    #[Test]
    public function it_refuses_when_nobody_is_signed_in(): void
    {
        $export = AccountExport::factory()->create([
            'status' => AccountExportStatusEnum::Ready,
            'expires_at' => now()->addDay(),
        ]);

        $this->assertFalse(Gate::forUser(null)->allows('create', AccountExport::class));
        $this->assertFalse(Gate::forUser(null)->allows('download', $export));
    }
}
