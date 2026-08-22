<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\GrantAdministratorAccess;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Designate an administrator from the command line.
 *
 * This is the one route into the capacity that does not already require it, and
 * it is deliberately the only one. It is how the first administrator of an
 * instance arrives, and how an instance left with nobody is recovered. What
 * authorises it is a shell on the server, which is the same thing that
 * authorises reading the database.
 *
 * There is no command that withdraws the capacity. Withdrawing happens in the
 * admin space, where the rule about the last administrator and the rule about
 * not doing it to yourself are enforced together.
 */
class MakeAdministratorCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'monica:make-administrator {email : Address of an existing user of this instance}';

    /**
     * @var string
     */
    protected $description = 'Let an existing user run this instance';

    public function handle(): int
    {
        $email = mb_strtolower((string) $this->argument('email'));

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
