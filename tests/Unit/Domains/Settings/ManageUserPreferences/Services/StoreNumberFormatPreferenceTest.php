<?php

namespace Tests\Unit\Domains\Settings\ManageUserPreferences\Services;

use App\Models\Account;
use App\Models\User;
use App\Settings\ManageUserPreferences\Services\StoreNumberFormatPreference;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StoreNumberFormatPreferenceTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_stores_the_number_format_preference(): void
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
        (new StoreNumberFormatPreference())->execute($request);
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
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'number_format' => 'Y',
        ];

        $user = (new StoreNumberFormatPreference())->execute($request);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $account->id,
            'number_format' => 'Y',
        ]);

        $this->assertInstanceOf(
            User::class,
            $user
        );
    }
}
