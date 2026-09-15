<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\GrantAdministratorAccess;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('monica:make-administrator {--email= : Address of an existing user of this instance}')]
#[Description('Let an existing user run this instance')]
class MakeAdministratorCommand extends Command
{
    public function handle(): int
    {
        $email = mb_strtolower((string) $this->option('email'));

        $user = User::query()->whereRaw('lower(email) = ?', [$email])->first();

        if ($user === null) {
            $this->error("Nobody on this instance uses the address {$email}.");

            return self::FAILURE;
        }

        if ($user->administrator()->exists()) {
            $this->error("{$user->name} already administers this instance.");

            return self::FAILURE;
        }

        new GrantAdministratorAccess(user: $user)->execute();

        $this->info("{$user->name} now administers this instance.");

        return self::SUCCESS;
    }
}
