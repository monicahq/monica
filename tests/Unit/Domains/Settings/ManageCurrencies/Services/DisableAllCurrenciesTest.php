<?php

namespace Tests\Unit\Domains\Settings\ManageCurrencies\Services;

use App\Domains\Settings\ManageCurrencies\Services\DisableAllCurrencies;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DisableAllCurrenciesTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_disables_all_currencies(): void
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
        (new DisableAllCurrencies)->execute($request);
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
        $currency = Currency::factory()->create();
        $account->currencies()->attach($currency->id, ['active' => true]);

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
        ];

        (new DisableAllCurrencies)->execute($request);

        $this->assertDatabaseHas('account_currency', [
            'account_id' => $account->id,
            'active' => false,
        ]);
    }
}
