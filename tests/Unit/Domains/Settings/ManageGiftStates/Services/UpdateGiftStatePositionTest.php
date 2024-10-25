<?php

namespace Tests\Unit\Domains\Settings\ManageGiftStates\Services;

use App\Domains\Settings\ManageGiftStates\Services\UpdateGiftStatePosition;
use App\Models\Account;
use App\Models\GiftState;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateGiftStatePositionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_state_position(): void
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
        (new UpdateGiftStatePosition)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $giftState = GiftState::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $account, $giftState);
    }

    /** @test */
    public function it_fails_if_gift_stage_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $giftState = GiftState::factory()->create();
        $this->executeService($ross, $ross->account, $giftState);
    }

    private function executeService(User $author, Account $account, GiftState $giftState): void
    {
        $giftState1 = GiftState::factory()->create([
            'account_id' => $account->id,
            'position' => 1,
        ]);
        $giftState3 = GiftState::factory()->create([
            'account_id' => $account->id,
            'position' => 3,
        ]);
        $giftState4 = GiftState::factory()->create([
            'account_id' => $account->id,
            'position' => 4,
        ]);

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'gift_state_id' => $giftState->id,
            'new_position' => 3,
        ];

        $giftState = (new UpdateGiftStatePosition)->execute($request);

        $this->assertDatabaseHas('gift_states', [
            'id' => $giftState1->id,
            'account_id' => $account->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('gift_states', [
            'id' => $giftState3->id,
            'account_id' => $account->id,
            'position' => 2,
        ]);
        $this->assertDatabaseHas('gift_states', [
            'id' => $giftState4->id,
            'account_id' => $account->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('gift_states', [
            'id' => $giftState->id,
            'account_id' => $account->id,
            'position' => 3,
        ]);

        $request['new_position'] = 2;

        $giftState = (new UpdateGiftStatePosition)->execute($request);

        $this->assertDatabaseHas('gift_states', [
            'id' => $giftState1->id,
            'account_id' => $account->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('gift_states', [
            'id' => $giftState3->id,
            'account_id' => $account->id,
            'position' => 3,
        ]);
        $this->assertDatabaseHas('gift_states', [
            'id' => $giftState4->id,
            'account_id' => $account->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('gift_states', [
            'id' => $giftState->id,
            'account_id' => $account->id,
            'position' => 2,
        ]);

        $this->assertInstanceOf(
            GiftState::class,
            $giftState
        );
    }
}
