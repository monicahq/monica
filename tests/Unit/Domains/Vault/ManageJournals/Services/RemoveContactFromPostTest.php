<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Services;

use App\Domains\Vault\ManageJournals\Services\RemoveContactFromPost;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Journal;
use App\Models\Post;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RemoveContactFromPostTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_removes_a_contact_from_a_post(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $journal = Journal::factory()->create(['vault_id' => $vault->id]);
        $post = Post::factory()->create(['journal_id' => $journal->id]);
        $post->contacts()->attach($contact->id);

        $this->executeService($regis, $regis->account, $vault, $contact, $journal, $post);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new RemoveContactFromPost)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = Account::factory()->create();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $journal = Journal::factory()->create(['vault_id' => $vault->id]);
        $post = Post::factory()->create(['journal_id' => $journal->id]);
        $post->contacts()->attach($contact->id);

        $this->executeService($regis, $account, $vault, $contact, $journal, $post);
    }

    /** @test */
    public function it_fails_if_post_doesnt_belong_to_journal(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $journal = Journal::factory()->create(['vault_id' => $vault->id]);
        $post = Post::factory()->create();
        $post->contacts()->attach($contact->id);

        $this->executeService($regis, $regis->account, $vault, $contact, $journal, $post);
    }

    /** @test */
    public function it_fails_if_journal_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $journal = Journal::factory()->create();
        $post = Post::factory()->create(['journal_id' => $journal->id]);
        $post->contacts()->attach($contact->id);

        $this->executeService($regis, $regis->account, $vault, $contact, $journal, $post);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $journal = Journal::factory()->create(['vault_id' => $vault->id]);
        $post = Post::factory()->create(['journal_id' => $journal->id]);
        $post->contacts()->attach($contact->id);

        $this->executeService($regis, $regis->account, $vault, $contact, $journal, $post);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $journal = Journal::factory()->create(['vault_id' => $vault->id]);
        $post = Post::factory()->create(['journal_id' => $journal->id]);
        $post->contacts()->attach($contact->id);

        $this->executeService($regis, $regis->account, $vault, $contact, $journal, $post);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, Journal $journal, Post $post): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'vault_id' => $vault->id,
            'journal_id' => $journal->id,
            'post_id' => $post->id,
            'contact_id' => $contact->id,
        ];

        (new RemoveContactFromPost)->execute($request);

        $this->assertDatabaseMissing('contact_post', [
            'contact_id' => $contact->id,
            'post_id' => $post->id,
        ]);
    }
}
