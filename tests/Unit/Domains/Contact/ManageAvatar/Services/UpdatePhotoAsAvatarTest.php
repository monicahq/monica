<?php

namespace Tests\Unit\Domains\Contact\ManageAvatar\Services;

use App\Domains\Contact\ManageAvatar\Services\UpdatePhotoAsAvatar;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\File;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdatePhotoAsAvatarTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_uploads_a_photo_as_avatar(): void
    {
        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $file = File::factory()->create([
            'vault_id' => $vault->id,
            'type' => File::TYPE_AVATAR,
        ]);

        $this->executeService($user, $user->account, $vault, $contact, $file);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdatePhotoAsAvatar)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = $this->createUser();
        $account = Account::factory()->create();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $file = File::factory()->create([
            'vault_id' => $vault->id,
            'type' => File::TYPE_AVATAR,
        ]);

        $this->executeService($user, $account, $vault, $contact, $file);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $file = File::factory()->create([
            'vault_id' => $vault->id,
            'type' => File::TYPE_AVATAR,
        ]);

        $this->executeService($user, $user->account, $vault, $contact, $file);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $file = File::factory()->create([
            'vault_id' => $vault->id,
            'type' => File::TYPE_AVATAR,
        ]);

        $this->executeService($user, $user->account, $vault, $contact, $file);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, File $file): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
            'file_id' => $file->id,
        ];

        $contact = (new UpdatePhotoAsAvatar)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'file_id' => $file->id,
        ]);

        $this->assertDatabaseHas('files', [
            'id' => $file->id,
            'vault_id' => $vault->id,
        ]);
    }
}
