<?php

namespace Tests\Unit\Domains\Settings\ManageGiftStates\Services;

use App\Domains\Settings\ManageGiftStates\Services\UpdateGiftState;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\GiftState;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateGiftStateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_gift_state(): void
    {
        $ross = $this->createAdministrator();
        $giftState = GiftState::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $giftState);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateGiftState)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $giftState = GiftState::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $account, $giftState);
    }

    /** @test */
    public function it_fails_if_type_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $giftState = GiftState::factory()->create();
        $this->executeService($ross, $ross->account, $giftState);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_account(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $giftState = GiftState::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $giftState);
    }

    private function executeService(User $author, Account $account, GiftState $giftState): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'gift_state_id' => $giftState->id,
            'label' => 'type label',
        ];

        $giftState = (new UpdateGiftState)->execute($request);

        $this->assertDatabaseHas('gift_states', [
            'id' => $giftState->id,
            'account_id' => $account->id,
            'label' => 'type label',
        ]);
    }
}
