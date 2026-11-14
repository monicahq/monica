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
use App\Jobs\SeedLocations;
use App\Jobs\SeedNotificationPreference;
use App\Jobs\SeedPossessionConditions;
use App\Jobs\SeedPossessionTypes;
use App\Jobs\SeedRelationshipTypes;
use App\Models\Account;
use App\Models\PreviewListEntry;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

/**
 * Create an account, the one user in it, the template a contact starts from and the first vault,
 * which holds the contact standing for that user, then ask an ordered chain of jobs for the rest of
 * what the account starts with.
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
        private readonly ?PreviewListEntry $previewListEntry = null,
    ) {}

    public function execute(): User
    {
        DB::transaction(function (): void {
            $this->createAccount();
            $this->createUser();
            $this->closeTheAdmission();
            $this->createTheContactTemplate();
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
            email: $this->previewListEntry->email ?? $this->email,
            password: $this->password,
            timezone: $this->timezone,
            locale: $this->locale,
            ssoProvider: $this->ssoProvider,
            ssoId: $this->ssoId,
        )->execute();
    }

    private function closeTheAdmission(): void
    {
        if ($this->previewListEntry === null) {
            return;
        }

        $this->user->markEmailAsVerified();

        $this->previewListEntry->update([
            'registered_at' => now(),
            'token' => null,
            'token_expires_at' => null,
        ]);
    }

    private function createTheContactTemplate(): void
    {
        new CreateDefaultValueLists(user: $this->user)->execute();

        new CreateDefaultTemplate(user: $this->user)->handle();
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
            new SeedLifeEventCategories(user: $this->user),
            new CreateDefaultTemplate(user: $this->user, supplied: DefaultTemplateEnum::BasicAnimal),
            new CreateDefaultLifeEventTypes(user: $this->user),
            new SeedRelationshipTypes(user: $this->user),
            new SeedPossessionTypes(user: $this->user),
            new SeedPossessionConditions(user: $this->user),
            new SeedLocations(user: $this->user),
        ])->onQueue('high')->dispatch();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->account,
            user: $this->user,
            action: UserActionEnum::AccountCreation,
        );
    }
}
