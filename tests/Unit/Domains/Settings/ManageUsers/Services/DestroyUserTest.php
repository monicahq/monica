<?php

namespace Tests\Unit\Domains\Settings\ManageUsers\Services;

use App\Domains\Settings\ManageUsers\Services\DestroyUser;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DestroyUserTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_deletes_another_user(): void
    {
        $author = $this->createAdministrator();
        $user = User::factory()->create([
            'account_id' => $author->account_id,
        ]);

        // we create three vaults:
        // - one where the user is manager
        // - one where the user is editor
        // - one where the user is viewer
        $vaultManager = Vault::factory()->create([
            'account_id' => $author->account_id,
        ]);
        $vaultManager->users()->save($user, [
            'permission' => Vault::PERMISSION_MANAGE,
            'contact_id' => Contact::factory()->create()->id,
        ]);
        $vaultEditor = Vault::factory()->create([
            'account_id' => $author->account_id,
        ]);
        $vaultEditor->users()->save($user, [
            'permission' => Vault::PERMISSION_EDIT,
            'contact_id' => Contact::factory()->create()->id,
        ]);
        $vaultEditor->users()->save(User::factory()->create(), [
            'permission' => Vault::PERMISSION_MANAGE,
            'contact_id' => Contact::factory()->create()->id,
        ]);
        $vaultViewer = Vault::factory()->create([
            'account_id' => $author->account_id,
        ]);
        $vaultViewer->users()->save($user, [
            'permission' => Vault::PERMISSION_VIEW,
            'contact_id' => Contact::factory()->create()->id,
        ]);
        $vaultViewer->users()->save(User::factory()->create(), [
            'permission' => Vault::PERMISSION_MANAGE,
            'contact_id' => Contact::factory()->create()->id,
        ]);

        $this->executeService($author->account, $author, $user, $vaultManager, $vaultEditor, $vaultViewer);
    }

    /** @test */
    public function it_fails_if_author_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $author = $this->createAdministrator();
        $account = Account::factory()->create();
        $user = User::factory()->create([
            'account_id' => $author->account_id,
        ]);
        $this->executeService($account, $author, $user);
    }

    /** @test */
    public function it_fails_if_user_is_not_account_administrator(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $author = $this->createUser();
        $user = User::factory()->create([
            'account_id' => $author->account_id,
        ]);
        $this->executeService($author->account, $author, $user);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new DestroyUser)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_is_not_in_the_same_account_as_the_other(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $author = $this->createAdministrator();
        $user = User::factory()->create();
        $this->executeService($author->account, $author, $user);
    }

    private function executeService(Account $account, User $author, User $user, ?Vault $vaultManager = null, ?Vault $vaultEditor = null, ?Vault $vaultViewer = null): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'user_id' => $user->id,
        ];

        (new DestroyUser)->execute($request);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
        $this->assertDatabaseMissing('vaults', [
            'id' => $vaultManager->id,
        ]);
        $this->assertDatabaseHas('vaults', [
            'id' => $vaultEditor->id,
        ]);
        $this->assertDatabaseHas('vaults', [
            'id' => $vaultViewer->id,
        ]);

        $this->assertDatabaseHas('user_vault', [
            'vault_id' => $vaultEditor->id,
        ]);
        $this->assertDatabaseHas('user_vault', [
            'vault_id' => $vaultViewer->id,
        ]);
    }
}
