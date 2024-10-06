<?php

namespace Tests\Unit\Domains\Settings\ManageCallReasons\Services;

use App\Domains\Settings\ManageCallReasons\Services\UpdateCallReasonType;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\CallReasonType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateCallReasonTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_type(): void
    {
        $ross = $this->createAdministrator();
        $type = CallReasonType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $type);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateCallReasonType)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $type = CallReasonType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $account, $type);
    }

    /** @test */
    public function it_fails_if_type_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $type = CallReasonType::factory()->create();
        $this->executeService($ross, $ross->account, $type);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_account(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $type = CallReasonType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $type);
    }

    private function executeService(User $author, Account $account, CallReasonType $type): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'call_reason_type_id' => $type->id,
            'label' => 'type name',
        ];

        $type = (new UpdateCallReasonType)->execute($request);

        $this->assertDatabaseHas('call_reason_types', [
            'id' => $type->id,
            'account_id' => $account->id,
            'label' => 'type name',
        ]);
    }
}
