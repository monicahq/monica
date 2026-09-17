<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SsoProviderEnum;
use App\Enums\UserActionEnum;
use App\Jobs\CreateDefaultActivityTypes;
use App\Jobs\CreateDefaultTemplate;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;

/**
 * Create an account, the one user in it, the vault their contacts go in, the value lists the
 * fields of that vault pick from, and the vocabulary its relationships and its activities are
 * described with.
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
        private readonly ?SsoProviderEnum $ssoProvider = null,
        private readonly ?string $ssoId = null,
    ) {}

    public function execute(): User
    {
        DB::transaction(function (): void {
            $this->createAccount();
            $this->createUser();
            $this->createFirstVault();
            $this->createDefaultValueLists();
            $this->createDefaultRelationshipTypes();
            $this->createDefaultActivityCategories();
            $this->createNotificationPreference();
        });

        event(new Registered($this->user));

        $this->createDefaultTemplate();
        $this->createDefaultActivityTypes();
        $this->log();

        return $this->user;
    }

    private function createAccount(): void
    {
        $this->account = Account::query()->create([
            'storage_used_in_bytes' => 0,
            'storage_limit_in_bytes' => config('monica.self_hosted') ? null : config('monica.free_storage_limit_in_bytes'),
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
            ssoProvider: $this->ssoProvider,
            ssoId: $this->ssoId,
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

    private function createDefaultActivityCategories(): void
    {
        new CreateDefaultActivityCategories(user: $this->user)->execute();
    }

    private function createDefaultRelationshipTypes(): void
    {
        new CreateDefaultRelationshipTypes(user: $this->user)->execute();
    }

    private function createNotificationPreference(): void
    {
        new CreateNotificationPreference(account: $this->account)->execute();
    }

    private function createDefaultTemplate(): void
    {
        CreateDefaultTemplate::dispatch(user: $this->user)->onQueue('high');
    }

    private function createDefaultActivityTypes(): void
    {
        CreateDefaultActivityTypes::dispatch(user: $this->user)->onQueue('high');
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
