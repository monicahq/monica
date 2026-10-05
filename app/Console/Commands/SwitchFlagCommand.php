<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\ActivateFlag;
use App\Actions\ActivateFlagForEveryAccount;
use App\Actions\DeactivateFlag;
use App\Actions\DeactivateFlagForEveryAccount;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;

#[Signature('monica:switch-flag {flag : Name of a declared flag} {state : on or off} {--email= : Address of the one account to switch it for} {--every-account : Switch it for every account}')]
#[Description('Switch a flag on or off for one account or for every account')]
class SwitchFlagCommand extends Command
{
    public function handle(): int
    {
        $flag = (string) $this->argument('flag');
        $state = (string) $this->argument('state');
        $email = $this->option('email');
        $everyAccount = (bool) $this->option('every-account');

        if (! in_array($state, ['on', 'off'], true)) {
            $this->error("The state is on or off, not {$state}.");

            return self::FAILURE;
        }

        if (($email !== null) === $everyAccount) {
            $this->error('Name one account with --email, or every account with --every-account.');

            return self::FAILURE;
        }

        try {
            if ($everyAccount) {
                $this->switchForEveryAccount($flag, $state);
                $this->info("{$flag} is now {$state} for every account.");

                return self::SUCCESS;
            }

            $email = mb_strtolower((string) $email);
            $user = User::query()->whereRaw('lower(email) = ?', [$email])->first();

            if ($user === null) {
                $this->error("Nobody on this instance uses the address {$email}.");

                return self::FAILURE;
            }

            $this->switchForOneAccount($flag, $state, $user);
            $this->info("{$flag} is now {$state} for {$user->name}.");

            return self::SUCCESS;
        } catch (ModelNotFoundException) {
            $this->error("No flag is named {$flag}.");

            return self::FAILURE;
        }
    }

    private function switchForEveryAccount(string $flag, string $state): void
    {
        if ($state === 'on') {
            new ActivateFlagForEveryAccount(flag: $flag)->execute();

            return;
        }

        new DeactivateFlagForEveryAccount(flag: $flag)->execute();
    }

    private function switchForOneAccount(string $flag, string $state, User $user): void
    {
        if ($state === 'on') {
            new ActivateFlag(account: $user->account, flag: $flag)->execute();

            return;
        }

        new DeactivateFlag(account: $user->account, flag: $flag)->execute();
    }
}
