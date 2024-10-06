<?php

namespace Tests\Unit\Domains\Vault\ManageVaultSettings\Services;

use App\Domains\Vault\ManageVaultSettings\Services\CreateMoodTrackingParameter;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\MoodTrackingParameter;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateMoodTrackingParameterTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_mood_tracking_parameter(): void
    {
        $ross = $this->createAdministrator();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $this->executeService($ross, $ross->account, $vault);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreateMoodTrackingParameter)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $this->executeService($ross, $account, $vault);
    }

    /** @test */
    public function it_fails_if_user_is_not_editor(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_VIEW, $vault);
        $this->executeService($ross, $ross->account, $vault);
    }

    /** @test */
    public function it_fails_if_vault_is_not_in_the_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $vault = $this->createVault(Account::factory()->create());
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_VIEW, $vault);
        $this->executeService($ross, $ross->account, $vault);
    }

    private function executeService(User $author, Account $account, Vault $vault): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'vault_id' => $vault->id,
            'label' => 'label name',
            'hex_color' => 'bg-zinc-700',
        ];

        $moodTrackingParameter = (new CreateMoodTrackingParameter)->execute($request);

        $this->assertDatabaseHas('mood_tracking_parameters', [
            'id' => $moodTrackingParameter->id,
            'vault_id' => $vault->id,
            'label' => 'label name',
            'hex_color' => 'bg-zinc-700',
        ]);

        $this->assertInstanceOf(
            MoodTrackingParameter::class,
            $moodTrackingParameter
        );
    }
}
