<?php

namespace Tests\Unit\Domains\Contact\ManageDocuments\Services;

use App\Domains\Contact\ManageDocuments\Services\UploadFile;
use App\Exceptions\EnvVariablesNotSetException;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\File;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UploadFileTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_uploads_a_file(): void
    {
        config(['services.uploadcare.public_key' => 'test']);
        config(['services.uploadcare.private_key' => 'test']);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);

        $this->executeService($regis, $regis->account, $vault);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        config(['services.uploadcare.public_key' => 'test']);
        config(['services.uploadcare.private_key' => 'test']);

        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UploadFile)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_when_env_keys_are_not_set(): void
    {
        config(['services.uploadcare.public_key' => null]);
        config(['services.uploadcare.public_key' => null]);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);

        $this->expectException(EnvVariablesNotSetException::class);
        $this->executeService($regis, $regis->account, $vault);

        config(['services.uploadcare.public_key' => 'test']);
        $this->expectException(EnvVariablesNotSetException::class);
        $this->executeService($regis, $regis->account, $vault);

        config(['services.uploadcare.private_key' => 'test']);
        $this->executeService($regis, $regis->account, $vault);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        config(['services.uploadcare.public_key' => 'test']);
        config(['services.uploadcare.private_key' => 'test']);

        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = Account::factory()->create();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);

        $this->executeService($regis, $account, $vault);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_initial_vault(): void
    {
        config(['services.uploadcare.public_key' => 'test']);
        config(['services.uploadcare.private_key' => 'test']);

        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);

        $this->executeService($regis, $regis->account, $vault);
    }

    private function executeService(User $author, Account $account, Vault $vault): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'uuid' => '017162da-e83b-46fc-89fc-3a7740db0a81',
            'name' => 'Twitter post.png',
            'original_url' => 'https://ucarecdn.com/5c8b9cea-62e5-4c8b-bc4c-47c0ddae62eee/',
            'cdn_url' => 'cdn_url',
            'mime_type' => 'image/jpg',
            'size' => 390340,
            'type' => 'avatar',
        ];

        $file = (new UploadFile)->execute($request);

        $this->assertInstanceOf(
            File::class,
            $file
        );

        $this->assertDatabaseHas('files', [
            'id' => $file->id,
            'vault_id' => $vault->id,
            'uuid' => '017162da-e83b-46fc-89fc-3a7740db0a81',
            'name' => 'Twitter post.png',
            'original_url' => 'https://ucarecdn.com/5c8b9cea-62e5-4c8b-bc4c-47c0ddae62eee/',
            'cdn_url' => 'cdn_url',
            'mime_type' => 'image/jpg',
            'size' => 390340,
            'type' => 'avatar',
        ]);
    }
}
