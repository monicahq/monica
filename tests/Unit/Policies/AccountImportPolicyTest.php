<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\AccountImportStatusEnum;
use App\Models\AccountImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountImportPolicyTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{AccountImportStatusEnum}> */
    public static function runningStatuses(): array
    {
        return [
            'being read' => [AccountImportStatusEnum::Reading],
            'being written' => [AccountImportStatusEnum::Writing],
            'being rolled back' => [AccountImportStatusEnum::RollingBack],
        ];
    }

    #[Test]
    public function it_allows_an_import_when_every_earlier_one_has_ended(): void
    {
        $dwight = User::factory()->create(['first_name' => 'Dwight', 'last_name' => 'Schrute']);
        AccountImport::factory()->create(['user_id' => $dwight->id, 'status' => AccountImportStatusEnum::Refused]);
        AccountImport::factory()->create(['user_id' => $dwight->id, 'status' => AccountImportStatusEnum::RolledBack]);
        AccountImport::factory()->create(['user_id' => $dwight->id, 'status' => AccountImportStatusEnum::Completed]);

        $this->assertTrue(Gate::forUser($dwight)->allows('create', AccountImport::class));
    }

    #[Test]
    #[DataProvider('runningStatuses')]
    public function it_refuses_an_import_while_one_is_running(AccountImportStatusEnum $status): void
    {
        $dwight = User::factory()->create(['first_name' => 'Dwight', 'last_name' => 'Schrute']);
        AccountImport::factory()->create(['user_id' => $dwight->id, 'status' => $status]);

        $response = Gate::forUser($dwight)->inspect('create', AccountImport::class);

        $this->assertTrue($response->denied());
        $this->assertSame('An import is already running.', $response->message());
    }

    #[Test]
    public function it_refuses_an_import_while_a_file_read_earlier_waits_for_a_decision(): void
    {
        $dwight = User::factory()->create(['first_name' => 'Dwight', 'last_name' => 'Schrute']);
        AccountImport::factory()->create(['user_id' => $dwight->id, 'status' => AccountImportStatusEnum::Read]);

        $response = Gate::forUser($dwight)->inspect('create', AccountImport::class);

        $this->assertTrue($response->denied());
        $this->assertSame('A file has been read and waits for you to start it or discard it.', $response->message());
    }

    #[Test]
    public function it_allows_an_import_while_another_account_runs_one(): void
    {
        $dwight = User::factory()->create(['first_name' => 'Dwight', 'last_name' => 'Schrute']);
        $mose = User::factory()->create(['first_name' => 'Mose', 'last_name' => 'Schrute']);
        AccountImport::factory()->create(['user_id' => $mose->id, 'status' => AccountImportStatusEnum::Writing]);

        $this->assertTrue(Gate::forUser($dwight)->allows('create', AccountImport::class));
    }

    #[Test]
    public function it_refuses_when_nobody_is_signed_in(): void
    {
        $this->assertFalse(Gate::forUser(null)->allows('create', AccountImport::class));
    }
}
