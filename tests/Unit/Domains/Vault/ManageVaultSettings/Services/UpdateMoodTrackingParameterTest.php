<?php

namespace Tests\Unit\Domains\Vault\ManageVaultSettings\Services;

use App\Domains\Vault\ManageVaultSettings\Services\UpdateMoodTrackingParameter;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\MoodTrackingParameter;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateMoodTrackingParameterTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_mood_tracking_parameter(): void
    {
        $ross = $this->createAdministrator();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $moodTrackingParameter = MoodTrackingParameter::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $this->executeService($ross, $ross->account, $vault, $moodTrackingParameter);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateMoodTrackingParameter)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $moodTrackingParameter = MoodTrackingParameter::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $this->executeService($ross, $account, $vault, $moodTrackingParameter);
    }

    /** @test */
    public function it_fails_if_parameter_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $moodTrackingParameter = MoodTrackingParameter::factory()->create();
        $this->executeService($ross, $ross->account, $vault, $moodTrackingParameter);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_account(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_VIEW, $vault);
        $moodTrackingParameter = MoodTrackingParameter::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $this->executeService($ross, $ross->account, $vault, $moodTrackingParameter);
    }

    private function executeService(User $author, Account $account, Vault $vault, MoodTrackingParameter $moodTrackingParameter): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'vault_id' => $vault->id,
            'mood_tracking_parameter_id' => $moodTrackingParameter->id,
            'label' => 'label name',
            'hex_color' => 'bg-zinc-700',
        ];

        $moodTrackingParameter = (new UpdateMoodTrackingParameter)->execute($request);

        $this->assertDatabaseHas('mood_tracking_parameters', [
            'id' => $moodTrackingParameter->id,
            'vault_id' => $vault->id,
            'label' => 'label name',
            'hex_color' => 'bg-zinc-700',
        ]);
    }
}
