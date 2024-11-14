<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Services;

use App\Domains\Vault\ManageJournals\Services\CreatePost;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Journal;
use App\Models\PostTemplate;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreatePostTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_post(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $journal);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreatePost)->execute($request);
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

        $this->executeService($regis, $account, $vault, $journal);
    }

    /** @test */
    public function it_fails_if_journal_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $journal = Journal::factory()->create();

        $this->executeService($regis, $regis->account, $vault, $journal);
    }

    /** @test */
    public function it_fails_if_post_template_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $journal = Journal::factory()->create();
        $postTemplate = PostTemplate::factory()->create();

        $this->executeService($regis, $regis->account, $vault, $journal, $postTemplate);
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

        $this->executeService($regis, $regis->account, $vault, $journal);
    }

    private function executeService(User $author, Account $account, Vault $vault, Journal $journal, ?PostTemplate $postTemplate = null): void
    {
        $postTemplate = $postTemplate ?? PostTemplate::factory()->create([
            'account_id' => $account->id,
        ]);

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'journal_id' => $journal->id,
            'post_template_id' => $postTemplate->id,
            'title' => 'title',
            'published' => true,
            'written_at' => null,
        ];

        $post = (new CreatePost)->execute($request);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'journal_id' => $journal->id,
            'title' => 'title',
        ]);
    }
}
