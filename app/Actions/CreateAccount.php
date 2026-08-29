<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\CreateDefaultTemplate;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Create an account, the one user in it, the vault their contacts go in, and
 * the value lists the fields of that vault pick from. The user is returned
 * rather than the account, because whoever asks for an account next has to sign
 * that person in.
 *
 * The product has to be usable in the first minute, so none of the seeding is
 * optional and none of it is asked for. Everything it writes is ordinary data
 * the user renames, reorders or throws away.
 *
 * Nobody should have to open a vault before writing anybody down, so the first
 * one is made here. It is the default one, being the only one, and an account
 * that later deletes every vault it has simply starts over from the screen it
 * started on. It is named in the language the account was created in, unlike
 * the value lists,
 * which are stored as translation keys and follow the interface language.
 *
 * The password is optional. An account created with a passkey has none from the
 * outset, which is the same state an account reaches by giving its password up
 * later, and CreateUser already leaves password_changed_at null to match.
 *
 * The template a contact is written down with is asked for rather than written
 * here, since nobody is looking at it while the sign-up page is still open. It
 * is asked for once the rows above are committed, so that a worker reaching for
 * the user cannot arrive before the user exists.
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
    ) {}

    public function execute(): User
    {
        DB::transaction(function (): void {
            $this->createAccount();
            $this->createUser();
            $this->createFirstVault();
            $this->createDefaultValueLists();
        });

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

    /**
     * The template comes after the lists, since two of its fields read their
     * choices from them.
     */
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
