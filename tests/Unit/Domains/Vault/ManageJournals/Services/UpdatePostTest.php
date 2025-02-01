<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Services;

use App\Domains\Vault\ManageJournals\Services\UpdatePost;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Journal;
use App\Models\Post;
use App\Models\PostSection;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdatePostTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_post(): void
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

        $this->executeService($regis, $regis->account, $vault, $journal, $post);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdatePost)->execute($request);
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

        $this->executeService($regis, $account, $vault, $journal, $post);
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

        $this->executeService($regis, $regis->account, $vault, $journal, $post);
    }

    /** @test */
    public function it_fails_if_post_doesnt_belong_to_journal(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $post = Post::factory()->create();

        $this->executeService($regis, $regis->account, $vault, $journal, $post);
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

        $this->executeService($regis, $regis->account, $vault, $journal, $post);
    }

    private function executeService(User $author, Account $account, Vault $vault, Journal $journal, Post $post): void
    {
        PostSection::factory()->create([
            'post_id' => $post->id,
            'content' => 'this is a content',
        ]);

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'journal_id' => $journal->id,
            'post_id' => $post->id,
            'title' => 'title',
            'sections' => [
                0 => [
                    'id' => PostSection::first()->id,
                    'content' => 'this is a content',
                ],
            ],
            'written_at' => null,
        ];

        $post = (new UpdatePost)->execute($request);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'journal_id' => $journal->id,
            'title' => 'title',
        ]);

        $this->assertDatabaseHas('post_sections', [
            'post_id' => $post->id,
            'content' => 'this is a content',
        ]);
    }
}
