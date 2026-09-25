<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DefaultTemplateEnum;
use App\Enums\SsoProviderEnum;
use App\Enums\UserActionEnum;
use App\Jobs\CreateDefaultLifeEventTypes;
use App\Jobs\CreateDefaultTemplate;
use App\Jobs\LogUserAction;
use App\Jobs\SeedLifeEventCategories;
use App\Jobs\SeedNotificationPreference;
use App\Jobs\SeedRelationshipTypes;
use App\Jobs\SeedValueLists;
use App\Models\Account;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

/**
 * Create an account, the one user in it and the vault their contacts go in, then ask an ordered
 * chain of jobs for the rest of what the account starts with.
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
        });

        event(new Registered($this->user));

        $this->seedTheAccount();
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

    private function seedTheAccount(): void
    {
        Bus::chain([
            new SeedNotificationPreference(user: $this->user),
            new SeedValueLists(user: $this->user),
            new SeedLifeEventCategories(user: $this->user),
            new CreateDefaultTemplate(user: $this->user),
            new CreateDefaultTemplate(user: $this->user, supplied: DefaultTemplateEnum::BasicAnimal),
            new CreateDefaultLifeEventTypes(user: $this->user),
            new SeedRelationshipTypes(user: $this->user),
        ])->onQueue('high')->dispatch();
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
