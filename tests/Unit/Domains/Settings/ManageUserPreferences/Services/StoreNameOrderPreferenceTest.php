<?php

namespace Tests\Unit\Domains\Settings\ManageUserPreferences\Services;

use App\Domains\Settings\ManageUserPreferences\Services\StoreNameOrderPreference;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StoreNameOrderPreferenceTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_stores_the_name_order_preference(): void
    {
        $ross = $this->createUser();
        $this->executeService($ross, '%name%', $ross->account);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new StoreNameOrderPreference)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $this->executeService($ross, '%user%', $account);
    }

    public function it_fails_if_name_order_has_no_variable(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $this->executeService($ross, '', $ross->account);
    }

    public function it_fails_if_name_order_has_no_closing_percent_symbol(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $this->executeService($ross, '%', $ross->account);
    }

    private function executeService(User $author, string $nameOrder, Account $account): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'name_order' => $nameOrder,
        ];

        $user = (new StoreNameOrderPreference)->execute($request);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $account->id,
            'name_order' => $nameOrder,
        ]);

        $this->assertInstanceOf(
            User::class,
            $user
        );
    }
}
