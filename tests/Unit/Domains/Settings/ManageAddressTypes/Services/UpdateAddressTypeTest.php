<?php

namespace Tests\Unit\Domains\Settings\ManageAddressTypes\Services;

use App\Domains\Settings\ManageAddressTypes\Services\UpdateAddressType;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\AddressType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateAddressTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_type(): void
    {
        $ross = $this->createAdministrator();
        $type = AddressType::factory()->create([
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
        (new UpdateAddressType)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $type = AddressType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $account, $type);
    }

    /** @test */
    public function it_fails_if_type_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $type = AddressType::factory()->create();
        $this->executeService($ross, $ross->account, $type);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_account(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $type = AddressType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $type);
    }

    private function executeService(User $author, Account $account, AddressType $type): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'address_type_id' => $type->id,
            'name' => 'type name',
        ];

        $type = (new UpdateAddressType)->execute($request);

        $this->assertDatabaseHas('address_types', [
            'id' => $type->id,
            'account_id' => $account->id,
            'name' => 'type name',
        ]);
    }
}
