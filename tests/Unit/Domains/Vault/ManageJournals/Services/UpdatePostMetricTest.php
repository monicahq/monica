<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Services;

use App\Domains\Vault\ManageJournals\Services\UpdatePostMetric;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalMetric;
use App\Models\Post;
use App\Models\PostMetric;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdatePostMetricTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_post_metric(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $post = Post::factory()->create([
            'journal_id' => $journal->id,
        ]);
        $journalMetric = JournalMetric::factory()->create([
            'journal_id' => $journal->id,
        ]);
        $postMetric = PostMetric::factory()->create([
            'post_id' => $post->id,
            'journal_metric_id' => $journalMetric->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $journal, $journalMetric, $post, $postMetric);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdatePostMetric)->execute($request);
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
        $post = Post::factory()->create([
            'journal_id' => $journal->id,
        ]);
        $journalMetric = JournalMetric::factory()->create([
            'journal_id' => $journal->id,
        ]);
        $postMetric = PostMetric::factory()->create([
            'post_id' => $post->id,
            'journal_metric_id' => $journalMetric->id,
        ]);

        $this->executeService($regis, $account, $vault, $journal, $journalMetric, $post, $postMetric);
    }

    /** @test */
    public function it_fails_if_journal_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $journal = Journal::factory()->create();
        $post = Post::factory()->create([
            'journal_id' => $journal->id,
        ]);
        $journalMetric = JournalMetric::factory()->create([
            'journal_id' => $journal->id,
        ]);
        $postMetric = PostMetric::factory()->create([
            'post_id' => $post->id,
            'journal_metric_id' => $journalMetric->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $journal, $journalMetric, $post, $postMetric);
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
        $post = Post::factory()->create([
            'journal_id' => $journal->id,
        ]);
        $journalMetric = JournalMetric::factory()->create();
        $postMetric = PostMetric::factory()->create([
            'post_id' => $post->id,
            'journal_metric_id' => $journalMetric->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $journal, $journalMetric, $post, $postMetric);
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
        $post = Post::factory()->create([
            'journal_id' => $journal->id,
        ]);
        $journalMetric = JournalMetric::factory()->create([
            'journal_id' => $journal->id,
        ]);
        $postMetric = PostMetric::factory()->create([
            'post_id' => $post->id,
            'journal_metric_id' => $journalMetric->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $journal, $journalMetric, $post, $postMetric);
    }

    private function executeService(User $author, Account $account, Vault $vault, Journal $journal, JournalMetric $journalMetric, Post $post, PostMetric $postMetric): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'journal_id' => $journal->id,
            'post_id' => $post->id,
            'journal_metric_id' => $journalMetric->id,
            'post_metric_id' => $postMetric->id,
            'label' => 'title',
            'value' => 1,
        ];

        $postMetric = (new UpdatePostMetric)->execute($request);

        $this->assertDatabaseHas('post_metrics', [
            'id' => $postMetric->id,
            'post_id' => $post->id,
            'journal_metric_id' => $journalMetric->id,
            'label' => 'title',
            'value' => 1,
        ]);
    }
}
