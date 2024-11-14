<?php

namespace Tests\Unit\Domains\Settings\ManageTemplates\Services;

use App\Domains\Settings\ManageTemplates\Services\DestroyModule;
use App\Models\Account;
use App\Models\Module;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DestroyModuleTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_module(): void
    {
        $ross = $this->createAdministrator();
        $module = $this->createModule($ross->account);
        $this->executeService($ross, $ross->account, $module);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new DestroyModule)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $module = $this->createModule($ross->account);
        $this->executeService($ross, $account, $module);
    }

    /** @test */
    public function it_fails_if_module_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $module = $this->createModule($account);
        $this->executeService($ross, $ross->account, $module);
    }

    /** @test */
    public function it_fails_if_module_cant_be_deleted(): void
    {
        $this->expectException(Exception::class);

        $ross = $this->createAdministrator();
        $module = $this->createModule($ross->account);
        $module->can_be_deleted = false;
        $module->save();
        $this->executeService($ross, $ross->account, $module);
    }

    private function executeService(User $author, Account $account, Module $module): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'module_id' => $module->id,
        ];

        (new DestroyModule)->execute($request);

        $this->assertDatabaseMissing('modules', [
            'id' => $module->id,
        ]);
    }

    private function createModule(Account $account): Module
    {
        $module = Module::factory()->create([
            'account_id' => $account->id,
        ]);

        return $module;
    }
}
