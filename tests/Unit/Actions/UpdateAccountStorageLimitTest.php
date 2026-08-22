<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateAccountStorageLimit;
use App\Jobs\LogAdministratorAction;
use App\Models\Account;
use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateAccountStorageLimitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sets_a_limit(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $account = Account::factory()->create(['storage_limit_in_bytes' => 1024]);

        new UpdateAccountStorageLimit(account: $account, administrator: $michael, limitInBytes: 2048)->execute();

        $this->assertEquals(2048, $account->refresh()->storage_limit_in_bytes);
    }

    #[Test]
    public function it_lifts_the_ceiling_entirely(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $account = Account::factory()->create(['storage_limit_in_bytes' => 1024]);

        new UpdateAccountStorageLimit(account: $account, administrator: $michael, limitInBytes: null)->execute();

        $this->assertNull($account->refresh()->storage_limit_in_bytes);
        $this->assertFalse($account->refresh()->isMetered());
    }

    #[Test]
    public function it_deletes_nothing_when_the_new_limit_is_under_what_is_already_held(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $account = Account::factory()->create([
            'storage_used_in_bytes' => 4096,
            'storage_limit_in_bytes' => 8192,
        ]);
        $file = File::factory()->create(['account_id' => $account->id]);

        new UpdateAccountStorageLimit(account: $account, administrator: $michael, limitInBytes: 1024)->execute();

        $this->assertEquals(1024, $account->refresh()->storage_limit_in_bytes);
        $this->assertEquals(4096, $account->refresh()->storage_used_in_bytes);
        $this->assertDatabaseHas('files', ['id' => $file->id]);
    }

    #[Test]
    public function it_writes_the_change_down_with_what_it_was_before(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $account = Account::factory()->create(['storage_limit_in_bytes' => 1024]);

        new UpdateAccountStorageLimit(account: $account, administrator: $michael, limitInBytes: 2048)->execute();

        Queue::assertPushedOn('low', LogAdministratorAction::class);
        Queue::assertPushed(LogAdministratorAction::class, fn (LogAdministratorAction $job): bool => $job->parameters['from'] === 1024
            && $job->parameters['to'] === 2048);
    }

    #[Test]
    public function it_lets_an_administrator_change_the_limit_on_their_own_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();

        new UpdateAccountStorageLimit(account: $michael->account, administrator: $michael, limitInBytes: 4096)->execute();

        $this->assertEquals(4096, $michael->account->refresh()->storage_limit_in_bytes);
    }
}
