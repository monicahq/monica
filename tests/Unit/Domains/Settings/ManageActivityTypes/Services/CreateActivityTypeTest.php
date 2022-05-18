<?php

namespace Tests\Unit\Domains\Settings\ManageActivityTypes\Services;

use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\ActivityType;
use App\Models\User;
use App\Settings\ManageActivityTypes\Services\CreateActivityType;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateActivityTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_an_activity_type(): void
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
        (new CreateActivityType)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $this->executeService($ross, $account);
    }

    /** @test */
    public function it_fails_if_user_is_not_administrator(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $this->executeService($ross, $ross->account);
    }

    private function executeService(User $author, Account $account): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'label' => 'type name',
        ];

        $type = (new CreateActivityType)->execute($request);

        $this->assertDatabaseHas('activity_types', [
            'id' => $type->id,
            'account_id' => $account->id,
            'label' => 'type name',
        ]);

        $this->assertInstanceOf(
            ActivityType::class,
            $type
        );
    }
}
