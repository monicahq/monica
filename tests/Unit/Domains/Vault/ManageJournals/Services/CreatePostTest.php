<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Services;

use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Journal;
use App\Models\PostTemplate;
use App\Models\PostTemplateSection;
use App\Models\User;
use App\Models\Vault;
use App\Vault\ManageJournals\Services\CreatePost;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
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
        $postTemplate = PostTemplate::factory()->create([
            'account_id' => $regis->account_id,
        ]);
        PostTemplateSection::factory()->create([
            'post_template_id' => $postTemplate->id,
            'position' => 1,
            'label' => 'Section 1',
        ]);
        PostTemplateSection::factory()->create([
            'post_template_id' => $postTemplate->id,
            'position' => 2,
            'label' => 'Section 2',
        ]);

        $this->executeService($regis, $regis->account, $vault, $journal, $postTemplate);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreatePost())->execute($request);
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
        $postTemplate = PostTemplate::factory()->create([
            'account_id' => $regis->account_id,
        ]);
        PostTemplateSection::factory()->create([
            'post_template_id' => $postTemplate->id,
            'position' => 1,
            'label' => 'Section 1',
        ]);
        PostTemplateSection::factory()->create([
            'post_template_id' => $postTemplate->id,
            'position' => 2,
            'label' => 'Section 2',
        ]);

        $this->executeService($regis, $account, $vault, $journal, $postTemplate);
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
        $postTemplate = PostTemplate::factory()->create([
            'account_id' => $regis->account_id,
        ]);
        PostTemplateSection::factory()->create([
            'post_template_id' => $postTemplate->id,
            'position' => 1,
            'label' => 'Section 1',
        ]);
        PostTemplateSection::factory()->create([
            'post_template_id' => $postTemplate->id,
            'position' => 2,
            'label' => 'Section 2',
        ]);

        $this->executeService($regis, $regis->account, $vault, $journal, $postTemplate);
    }

    /** @test */
    public function it_fails_if_journal_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $journal = Journal::factory()->create();
        $postTemplate = PostTemplate::factory()->create([
            'account_id' => $regis->account_id,
        ]);
        PostTemplateSection::factory()->create([
            'post_template_id' => $postTemplate->id,
            'position' => 1,
            'label' => 'Section 1',
        ]);
        PostTemplateSection::factory()->create([
            'post_template_id' => $postTemplate->id,
            'position' => 2,
            'label' => 'Section 2',
        ]);

        $this->executeService($regis, $regis->account, $vault, $journal, $postTemplate);
    }

    /** @test */
    public function it_fails_if_template_is_not_in_the_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $postTemplate = PostTemplate::factory()->create([]);
        PostTemplateSection::factory()->create([
            'post_template_id' => $postTemplate->id,
            'position' => 1,
            'label' => 'Section 1',
        ]);
        PostTemplateSection::factory()->create([
            'post_template_id' => $postTemplate->id,
            'position' => 2,
            'label' => 'Section 2',
        ]);

        $this->executeService($regis, $regis->account, $vault, $journal, $postTemplate);
    }

    private function executeService(User $author, Account $account, Vault $vault, Journal $journal, PostTemplate $postTemplate): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'journal_id' => $journal->id,
            'post_template_id' => $postTemplate->id,
            'title' => 'this is a title',
            'published' => true,
            'written_at' => '2020-01-01',
        ];

        $post = (new CreatePost())->execute($request);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'journal_id' => $journal->id,
            'title' => 'this is a title',
            'written_at' => '2020-01-01 00:00:00',
        ]);

        $this->assertDatabaseHas('post_sections', [
            'post_id' => $post->id,
            'position' => 1,
            'label' => 'Section 1',
        ]);
        $this->assertDatabaseHas('post_sections', [
            'post_id' => $post->id,
            'position' => 2,
            'label' => 'Section 2',
        ]);
    }
}
