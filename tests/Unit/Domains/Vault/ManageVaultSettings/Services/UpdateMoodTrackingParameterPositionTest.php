<?php

namespace Tests\Unit\Domains\Vault\ManageVaultSettings\Services;

use App\Domains\Vault\ManageVaultSettings\Services\UpdateMoodTrackingParameterPosition;
use App\Models\Account;
use App\Models\MoodTrackingParameter;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateMoodTrackingParameterPositionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_mood_tracking_parameter_position(): void
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
        (new UpdateMoodTrackingParameterPosition)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
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

    private function executeService(User $author, Account $account, Vault $vault, MoodTrackingParameter $moodTrackingParameter): void
    {
        $moodTrackingParameter1 = MoodTrackingParameter::factory()->create([
            'vault_id' => $vault->id,
            'position' => 1,
        ]);
        $moodTrackingParameter3 = MoodTrackingParameter::factory()->create([
            'vault_id' => $vault->id,
            'position' => 3,
        ]);
        $moodTrackingParameter4 = MoodTrackingParameter::factory()->create([
            'vault_id' => $vault->id,
            'position' => 4,
        ]);

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'mood_tracking_parameter_id' => $moodTrackingParameter->id,
            'new_position' => 3,
        ];

        $moodTrackingParameter = (new UpdateMoodTrackingParameterPosition)->execute($request);

        $this->assertDatabaseHas('mood_tracking_parameters', [
            'id' => $moodTrackingParameter1->id,
            'vault_id' => $vault->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('mood_tracking_parameters', [
            'id' => $moodTrackingParameter3->id,
            'vault_id' => $vault->id,
            'position' => 2,
        ]);
        $this->assertDatabaseHas('mood_tracking_parameters', [
            'id' => $moodTrackingParameter4->id,
            'vault_id' => $vault->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('mood_tracking_parameters', [
            'id' => $moodTrackingParameter->id,
            'vault_id' => $vault->id,
            'position' => 3,
        ]);

        $request['new_position'] = 2;

        $moodTrackingParameter = (new UpdateMoodTrackingParameterPosition)->execute($request);

        $this->assertDatabaseHas('mood_tracking_parameters', [
            'id' => $moodTrackingParameter1->id,
            'vault_id' => $vault->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('mood_tracking_parameters', [
            'id' => $moodTrackingParameter3->id,
            'vault_id' => $vault->id,
            'position' => 3,
        ]);
        $this->assertDatabaseHas('mood_tracking_parameters', [
            'id' => $moodTrackingParameter4->id,
            'vault_id' => $vault->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('mood_tracking_parameters', [
            'id' => $moodTrackingParameter->id,
            'vault_id' => $vault->id,
            'position' => 2,
        ]);

        $this->assertInstanceOf(
            MoodTrackingParameter::class,
            $moodTrackingParameter
        );
    }
}
