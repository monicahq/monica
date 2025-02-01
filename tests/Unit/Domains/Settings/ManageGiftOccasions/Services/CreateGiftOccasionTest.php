<?php

namespace Tests\Unit\Domains\Settings\ManageGiftOccasions\Services;

use App\Domains\Settings\ManageGiftOccasions\Services\CreateGiftOccasion;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\GiftOccasion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateGiftOccasionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_gift_occasion(): void
    {
        $ross = $this->createAdministrator();
        $this->executeService($ross, $ross->account);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreateGiftOccasion)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $this->executeService($ross, $account);
    }

    /** @test */
    public function it_fails_if_user_is_not_administrator(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $this->executeService($ross, $ross->account);
    }

    private function executeService(User $author, Account $account): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'label' => 'type name',
        ];

        $occasion = (new CreateGiftOccasion)->execute($request);

        $this->assertDatabaseHas('gift_occasions', [
            'id' => $occasion->id,
            'account_id' => $account->id,
            'label' => 'type name',
            'position' => 1,
        ]);

        $this->assertInstanceOf(
            GiftOccasion::class,
            $occasion
        );
    }
}
