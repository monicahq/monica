<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Services;

use App\Domains\Vault\ManageJournals\Services\RemoveTag;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Journal;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RemoveTagTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_removes_a_tag(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $tag = Tag::factory()->create(['vault_id' => $vault->id]);
        $journal = Journal::factory()->create(['vault_id' => $vault->id]);
        $post = Post::factory()->create(['journal_id' => $journal->id]);
        $post->tags()->syncWithoutDetaching($tag);

        $this->executeService($regis, $regis->account, $vault, $journal, $post, $tag);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new RemoveTag)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = Account::factory()->create();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $tag = Tag::factory()->create(['vault_id' => $vault->id]);
        $journal = Journal::factory()->create(['vault_id' => $vault->id]);
        $post = Post::factory()->create(['journal_id' => $journal->id]);
        $post->tags()->syncWithoutDetaching($tag);

        $this->executeService($regis, $account, $vault, $journal, $post, $tag);
    }

    /** @test */
    public function it_fails_if_tag_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $tag = Tag::factory()->create();
        $journal = Journal::factory()->create(['vault_id' => $vault->id]);
        $post = Post::factory()->create(['journal_id' => $journal->id]);
        $post->tags()->syncWithoutDetaching($tag);

        $this->executeService($regis, $regis->account, $vault, $journal, $post, $tag);
    }

    /** @test */
    public function it_fails_if_journal_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $tag = Tag::factory()->create(['vault_id' => $vault->id]);
        $journal = Journal::factory()->create();
        $post = Post::factory()->create(['journal_id' => $journal->id]);
        $post->tags()->syncWithoutDetaching($tag);

        $this->executeService($regis, $regis->account, $vault, $journal, $post, $tag);
    }

    /** @test */
    public function it_fails_if_post_doesnt_belong_to_journal(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $tag = Tag::factory()->create(['vault_id' => $vault->id]);
        $journal = Journal::factory()->create(['vault_id' => $vault->id]);
        $post = Post::factory()->create();
        $post->tags()->syncWithoutDetaching($tag);

        $this->executeService($regis, $regis->account, $vault, $journal, $post, $tag);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $tag = Tag::factory()->create(['vault_id' => $vault->id]);
        $journal = Journal::factory()->create(['vault_id' => $vault->id]);
        $post = Post::factory()->create(['journal_id' => $journal->id]);
        $post->tags()->syncWithoutDetaching($tag);

        $this->executeService($regis, $regis->account, $vault, $journal, $post, $tag);
    }

    private function executeService(User $author, Account $account, Vault $vault, Journal $journal, Post $post, Tag $tag): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'vault_id' => $vault->id,
            'tag_id' => $tag->id,
            'journal_id' => $journal->id,
            'post_id' => $post->id,
        ];

        (new RemoveTag)->execute($request);

        $this->assertDatabaseMissing('post_tag', [
            'post_id' => $post->id,
            'tag_id' => $tag->id,
        ]);
    }
}
