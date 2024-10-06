<?php

namespace Tests\Unit\Domains\Vault\ManageVaultSettings\Services;

use App\Domains\Vault\ManageVaultSettings\Services\UpdateLifeEventTypePosition;
use App\Models\Account;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateLifeEventTypePositionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_life_event_type_position(): void
    {
        $ross = $this->createAdministrator();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_EDIT, $vault);
        $lifeEventCategory = LifeEventCategory::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $lifeEventType = LifeEventType::factory()->create([
            'life_event_category_id' => $lifeEventCategory->id,
            'position' => 2,
        ]);
        $this->executeService($ross, $ross->account, $vault, $lifeEventCategory, $lifeEventType);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateLifeEventTypePosition)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_EDIT, $vault);
        $lifeEventCategory = LifeEventCategory::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $lifeEventType = LifeEventType::factory()->create([
            'life_event_category_id' => $lifeEventCategory->id,
        ]);
        $this->executeService($ross, $account, $vault, $lifeEventCategory, $lifeEventType);
    }

    /** @test */
    public function it_fails_if_life_event_type_doesnt_belong_to_category(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_EDIT, $vault);
        $lifeEventCategory = LifeEventCategory::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $lifeEventType = LifeEventType::factory()->create();
        $this->executeService($ross, $ross->account, $vault, $lifeEventCategory, $lifeEventType);
    }

    /** @test */
    public function it_fails_if_life_event_category_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_EDIT, $vault);
        $lifeEventCategory = LifeEventCategory::factory()->create();
        $lifeEventType = LifeEventType::factory()->create([
            'life_event_category_id' => $lifeEventCategory->id,
        ]);
        $this->executeService($ross, $ross->account, $vault, $lifeEventCategory, $lifeEventType);
    }

    private function executeService(User $author, Account $account, Vault $vault, LifeEventCategory $lifeEventCategory, LifeEventType $lifeEventType): void
    {
        $lifeEventType1 = LifeEventType::factory()->create([
            'life_event_category_id' => $lifeEventCategory->id,
            'position' => 1,
        ]);
        $lifeEventType3 = LifeEventType::factory()->create([
            'life_event_category_id' => $lifeEventCategory->id,
            'position' => 3,
        ]);
        $lifeEventType4 = LifeEventType::factory()->create([
            'life_event_category_id' => $lifeEventCategory->id,
            'position' => 4,
        ]);

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'life_event_category_id' => $lifeEventCategory->id,
            'life_event_type_id' => $lifeEventType->id,
            'new_position' => 3,
        ];

        $lifeEventType = (new UpdateLifeEventTypePosition)->execute($request);

        $this->assertDatabaseHas('life_event_types', [
            'id' => $lifeEventType1->id,
            'life_event_category_id' => $lifeEventCategory->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'id' => $lifeEventType3->id,
            'life_event_category_id' => $lifeEventCategory->id,
            'position' => 2,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'id' => $lifeEventType4->id,
            'life_event_category_id' => $lifeEventCategory->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'id' => $lifeEventType->id,
            'life_event_category_id' => $lifeEventCategory->id,
            'position' => 3,
        ]);

        $request['new_position'] = 2;

        $lifeEventType = (new UpdateLifeEventTypePosition)->execute($request);

        $this->assertDatabaseHas('life_event_types', [
            'id' => $lifeEventType1->id,
            'life_event_category_id' => $lifeEventCategory->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'id' => $lifeEventType3->id,
            'life_event_category_id' => $lifeEventCategory->id,
            'position' => 3,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'id' => $lifeEventType4->id,
            'life_event_category_id' => $lifeEventCategory->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('life_event_types', [
            'id' => $lifeEventType->id,
            'life_event_category_id' => $lifeEventCategory->id,
            'position' => 2,
        ]);

        $this->assertInstanceOf(
            LifeEventType::class,
            $lifeEventType
        );
    }
}
