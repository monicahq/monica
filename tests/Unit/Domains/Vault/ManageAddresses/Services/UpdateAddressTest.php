<?php

namespace Tests\Unit\Domains\Vault\ManageAddresses\Services;

use App\Domains\Vault\ManageAddresses\Services\GetGPSCoordinate;
use App\Domains\Vault\ManageAddresses\Services\UpdateAddress;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Address;
use App\Models\AddressType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateAddressTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_contact_address(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $type = AddressType::factory()->create(['account_id' => $regis->account_id]);
        $address = Address::factory()->create([
            'address_type_id' => $type->id,
            'vault_id' => $vault->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $type, $address);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateAddress)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = Account::factory()->create();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $type = AddressType::factory()->create(['account_id' => $regis->account_id]);
        $address = Address::factory()->create([
            'address_type_id' => $type->id,
            'vault_id' => $vault->id,
        ]);

        $this->executeService($regis, $account, $vault, $type, $address);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_initial_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $type = AddressType::factory()->create(['account_id' => $regis->account_id]);
        $address = Address::factory()->create([
            'address_type_id' => $type->id,
            'vault_id' => $vault->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $type, $address);
    }

    /** @test */
    public function it_fails_if_type_is_not_in_the_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $type = AddressType::factory()->create();
        $address = Address::factory()->create([
            'address_type_id' => $type->id,
            'vault_id' => $vault->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $type, $address);
    }

    /** @test */
    public function it_fails_if_address_does_not_exist(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $type = AddressType::factory()->create(['account_id' => $regis->account_id]);
        $address = Address::factory()->create();

        $this->executeService($regis, $regis->account, $vault, $type, $address);
    }

    private function executeService(User $author, Account $account, Vault $vault, AddressType $type, Address $address): void
    {
        config(['monica.location_iq_api_key' => '12345']);

        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'address_type_id' => $type->id,
            'address_id' => $address->id,
            'line_1' => '25 grand rue',
            'line_2' => 'Apartment 3',
            'city' => 'paris',
            'province' => '67',
            'postal_code' => '12344',
            'country' => 'FRA',
            'latitude' => 12345,
            'longitude' => 12345,
        ];

        $address = (new UpdateAddress)->execute($request);

        $this->assertDatabaseHas('addresses', [
            'vault_id' => $vault->id,
            'address_type_id' => $type->id,
            'line_1' => '25 grand rue',
            'line_2' => 'Apartment 3',
            'city' => 'paris',
            'province' => '67',
            'postal_code' => '12344',
            'country' => 'FRA',
            'latitude' => 12345,
            'longitude' => 12345,
        ]);

        $this->assertInstanceOf(
            Address::class,
            $address
        );

        Queue::assertPushed(GetGPSCoordinate::class, fn ($job) => $job->data['address_id'] === $address->id);
    }
}
