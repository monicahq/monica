<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\CreateDefaultTemplate;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;

/**
 * Create an account, the one user in it, the vault their contacts go in, and the value lists the
 * fields of that vault pick from.
 */
class CreateAccount
{
    private Account $account;

    private User $user;

    public function __construct(
        private readonly string $firstName,
        private readonly string $lastName,
        private readonly string $email,
        private readonly ?string $password = null,
        private readonly ?string $timezone = null,
        private readonly ?string $locale = null,
    ) {}

    public function execute(): User
    {
        DB::transaction(function (): void {
            $this->createAccount();
            $this->createUser();
            $this->createFirstVault();
            $this->createDefaultValueLists();
            $this->createNotificationPreference();
        });

        event(new Registered($this->user));

        $this->createDefaultTemplate();
        $this->log();

        return $this->user;
    }

    private function createAccount(): void
    {
        $isSelfHosted = (bool) config('monica.self_hosted');

        $this->account = Account::query()->create([
            'is_self_hosted' => $isSelfHosted,
            'has_lifetime_access' => false,
            'storage_used_in_bytes' => 0,
            'storage_limit_in_bytes' => $isSelfHosted ? null : config('monica.free_storage_limit_in_bytes'),
        ]);
    }

    private function createUser(): void
    {
        $this->user = new CreateUser(
            account: $this->account,
            firstName: $this->firstName,
            lastName: $this->lastName,
            email: $this->email,
            password: $this->password,
            timezone: $this->timezone,
            locale: $this->locale,
        )->execute();
    }

    private function createFirstVault(): void
    {
        new CreateVault(
            user: $this->user,
            name: __('Personal'),
        )->execute();
    }

    private function createDefaultValueLists(): void
    {
        new CreateDefaultValueLists(user: $this->user)->execute();
    }

    private function createNotificationPreference(): void
    {
        new CreateNotificationPreference(account: $this->account)->execute();
    }

    private function createDefaultTemplate(): void
    {
        CreateDefaultTemplate::dispatch(user: $this->user)->onQueue('high');
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->account,
            user: $this->user,
            action: UserActionEnum::AccountCreation,
        )->onQueue('low');
    }
}
