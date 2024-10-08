<?php

namespace Tests\Unit\Domains\Settings\ManageTemplates\Services;

use App\Domains\Settings\ManageTemplates\Services\CreateModule;
use App\Models\Account;
use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateModuleTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_module(): void
    {
        $ross = $this->createAdministrator();
        $this->executeService($ross, $ross->account);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreateModule)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $this->executeService($ross, $account);
    }

    private function executeService(User $author, Account $account): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'name' => 'Business',
            'can_be_deleted' => false,
        ];

        $module = (new CreateModule)->execute($request);

        $this->assertDatabaseHas('modules', [
            'id' => $module->id,
            'account_id' => $account->id,
            'name' => 'Business',
            'can_be_deleted' => false,
        ]);

        $this->assertInstanceOf(
            Module::class,
            $module
        );
    }
}
