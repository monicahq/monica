<?php

namespace Tests\Unit\Domains\Settings\ManageUserPreferences\Services;

use App\Domains\Settings\ManageUserPreferences\Services\StoreAvatarStylePreference;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StoreAvatarStylePreferenceTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_stores_the_avatar_tyle_preferences(): void
    {
        $ross = $this->createUser();
        $this->executeService($ross, $ross->account);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new StoreAvatarStylePreference)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $this->executeService($ross, $account);
    }

    private function executeService(User $author, Account $account): void
    {
        $avatarStyle = 'placeholder-dicebear-style';

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'avatar_style' => $avatarStyle,
        ];

        $user = (new StoreAvatarStylePreference)->execute($request);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $account->id,
            'avatar_style' => $avatarStyle,
        ]);

        $this->assertInstanceOf(
            User::class,
            $user
        );
    }
}
