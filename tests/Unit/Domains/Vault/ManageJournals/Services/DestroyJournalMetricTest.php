<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Services;

use App\Domains\Vault\ManageJournals\Services\DestroyJournalMetric;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalMetric;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DestroyJournalMetricTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_journal_metric(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $journalMetric = JournalMetric::factory()->create([
            'journal_id' => $journal->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $journal, $journalMetric);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new DestroyJournalMetric)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = Account::factory()->create();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $journalMetric = JournalMetric::factory()->create([
            'journal_id' => $journal->id,
        ]);

        $this->executeService($regis, $account, $vault, $journal, $journalMetric);
    }

    /** @test */
    public function it_fails_if_journal_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $journal = Journal::factory()->create();
        $journalMetric = JournalMetric::factory()->create([
            'journal_id' => $journal->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $journal, $journalMetric);
    }

    /** @test */
    public function it_fails_if_journal_metric_doesnt_belong_to_journal(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $journalMetric = JournalMetric::factory()->create();

        $this->executeService($regis, $regis->account, $vault, $journal, $journalMetric);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $journalMetric = JournalMetric::factory()->create([
            'journal_id' => $journal->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $journal, $journalMetric);
    }

    /** @test */
    public function it_fails_if_reminder_does_not_exist(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $journal = Journal::factory()->create();
        $journalMetric = JournalMetric::factory()->create([
            'journal_id' => $journal->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $journal, $journalMetric);
    }

    private function executeService(User $author, Account $account, Vault $vault, Journal $journal, JournalMetric $journalMetric): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'journal_id' => $journal->id,
            'journal_metric_id' => $journalMetric->id,
        ];

        (new DestroyJournalMetric)->execute($request);

        $this->assertDatabaseMissing('journal_metrics', [
            'id' => $journalMetric->id,
        ]);
    }
}
