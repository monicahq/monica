<?php

namespace Tests\Unit\Domains\Settings\ManageGiftOccasions\Services;

use App\Domains\Settings\ManageGiftOccasions\Services\UpdateGiftOccasionPosition;
use App\Models\Account;
use App\Models\GiftOccasion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateGiftOccasionPositionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_template_page_position(): void
    {
        $ross = $this->createAdministrator();
        $giftOccasion = GiftOccasion::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $giftOccasion);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateGiftOccasionPosition)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $giftOccasion = GiftOccasion::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $account, $giftOccasion);
    }

    /** @test */
    public function it_fails_if_gift_occasion_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $giftOccasion = GiftOccasion::factory()->create();
        $this->executeService($ross, $ross->account, $giftOccasion);
    }

    private function executeService(User $author, Account $account, GiftOccasion $giftOccasion): void
    {
        $giftOccasion1 = GiftOccasion::factory()->create([
            'account_id' => $account->id,
            'position' => 1,
        ]);
        $giftOccasion3 = GiftOccasion::factory()->create([
            'account_id' => $account->id,
            'position' => 3,
        ]);
        $giftOccasion4 = GiftOccasion::factory()->create([
            'account_id' => $account->id,
            'position' => 4,
        ]);

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'gift_occasion_id' => $giftOccasion->id,
            'new_position' => 3,
        ];

        $giftOccasion = (new UpdateGiftOccasionPosition)->execute($request);

        $this->assertDatabaseHas('gift_occasions', [
            'id' => $giftOccasion1->id,
            'account_id' => $account->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('gift_occasions', [
            'id' => $giftOccasion3->id,
            'account_id' => $account->id,
            'position' => 2,
        ]);
        $this->assertDatabaseHas('gift_occasions', [
            'id' => $giftOccasion4->id,
            'account_id' => $account->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('gift_occasions', [
            'id' => $giftOccasion->id,
            'account_id' => $account->id,
            'position' => 3,
        ]);

        $request['new_position'] = 2;

        $giftOccasion = (new UpdateGiftOccasionPosition)->execute($request);

        $this->assertDatabaseHas('gift_occasions', [
            'id' => $giftOccasion1->id,
            'account_id' => $account->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('gift_occasions', [
            'id' => $giftOccasion3->id,
            'account_id' => $account->id,
            'position' => 3,
        ]);
        $this->assertDatabaseHas('gift_occasions', [
            'id' => $giftOccasion4->id,
            'account_id' => $account->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('gift_occasions', [
            'id' => $giftOccasion->id,
            'account_id' => $account->id,
            'position' => 2,
        ]);

        $this->assertInstanceOf(
            GiftOccasion::class,
            $giftOccasion
        );
    }
}
