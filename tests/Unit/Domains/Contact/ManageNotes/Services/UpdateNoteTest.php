<?php

namespace Tests\Unit\Domains\Contact\ManageNotes\Services;

use Tests\TestCase;
use App\Models\Note;
use App\Models\User;
use App\Models\Vault;
use App\Models\Account;
use App\Models\Contact;
use App\Jobs\CreateAuditLog;
use App\Jobs\CreateContactLog;
use Illuminate\Support\Facades\Queue;
use App\Models\ContactInformationType;
use Illuminate\Validation\ValidationException;
use App\Contact\ManageNotes\Services\UpdateNote;
use App\Exceptions\NotEnoughPermissionException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateNoteTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_note(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $note = Note::factory()->create([
            'contact_id' => $contact->id,
            'author_id' => $regis->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $note);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateNote)->execute($request);
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
        $note = Note::factory()->create([
            'contact_id' => $contact->id,
            'author_id' => $regis->id,
        ]);

        $this->executeService($regis, $account, $vault, $contact, $note);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $note = Note::factory()->create([
            'contact_id' => $contact->id,
            'author_id' => $regis->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $note);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_initial_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $note = Note::factory()->create([
            'contact_id' => $contact->id,
            'author_id' => $regis->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $note);
    }

    /** @test */
    public function it_fails_if_note_is_not_in_the_contact(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $note = ContactInformationType::factory()->create();
        $note = Note::factory()->create([
            'author_id' => $regis->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $note);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, Note $note): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
            'note_id' => $note->id,
            'title' => 'super title',
            'body' => 'super body',
        ];

        $note = (new UpdateNote)->execute($request);

        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'contact_id' => $contact->id,
            'author_id' => $author->id,
            'title' => 'super title',
            'body' => 'super body',
        ]);

        Queue::assertPushed(CreateAuditLog::class, function ($job) {
            return $job->auditLog['action_name'] === 'note_updated';
        });

        Queue::assertPushed(CreateContactLog::class, function ($job) {
            return $job->contactLog['action_name'] === 'note_updated';
        });
    }
}
