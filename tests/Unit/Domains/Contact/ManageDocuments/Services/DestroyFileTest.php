<?php

namespace Tests\Unit\Domains\Contact\ManageDocuments\Services;

use App\Domains\Contact\ManageDocuments\Services\DestroyFile;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\File;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DestroyFileTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_file(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $file = File::factory()->create([
            'vault_id' => $vault->id,
            'type' => File::TYPE_DOCUMENT,
        ]);

        $this->executeService($regis, $regis->account, $vault, $file);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new DestroyFile)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = Account::factory()->create();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $file = File::factory()->create([
            'vault_id' => $vault->id,
            'type' => File::TYPE_DOCUMENT,
        ]);

        $this->executeService($regis, $account, $vault, $file);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_initial_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $file = File::factory()->create([
            'vault_id' => $vault->id,
            'type' => File::TYPE_DOCUMENT,
        ]);

        $this->executeService($regis, $regis->account, $vault, $file);
    }

    private function executeService(User $author, Account $account, Vault $vault, File $file): void
    {
        Event::fake();

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'file_id' => $file->id,
        ];

        (new DestroyFile)->execute($request);

        $this->assertDatabaseMissing('files', [
            'id' => $file->id,
        ]);
    }
}
