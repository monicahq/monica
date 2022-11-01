<?php

namespace Tests\Unit\Domains\Contact\ManagePhotos\Services;

use App\Domains\Contact\ManagePhotos\Services\DestroyPhoto;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\File;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DestroyPhotoTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_photo(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $file = File::factory()->create([
            'contact_id' => $contact->id,
            'type' => File::TYPE_PHOTO,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $file);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new DestroyPhoto())->execute($request);
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
        $file = File::factory()->create([
            'contact_id' => $contact->id,
            'type' => File::TYPE_PHOTO,
        ]);

        $this->executeService($regis, $account, $vault, $contact, $file);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $file = File::factory()->create([
            'contact_id' => $contact->id,
            'type' => File::TYPE_PHOTO,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $file);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_initial_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $file = File::factory()->create([
            'contact_id' => $contact->id,
            'type' => File::TYPE_PHOTO,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $file);
    }

    /** @test */
    public function it_fails_if_file_doesnt_belong_to_contact(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $file = File::factory()->create([
            'type' => File::TYPE_PHOTO,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $file);
    }

    /** @test */
    public function it_fails_if_file_is_not_a_PHOTO(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $file = File::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $file);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, File $file): void
    {
        Event::fake();

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
            'file_id' => $file->id,
        ];

        (new DestroyPhoto())->execute($request);

        $this->assertDatabaseMissing('files', [
            'id' => $file->id,
        ]);
    }
}
