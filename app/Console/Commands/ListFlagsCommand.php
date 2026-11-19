<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Helpers\FlagState;
use App\Models\Account;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Laravel\Pennant\Feature;

#[Signature('monica:list-flags')]
#[Description('List the flags this instance declares, whether each is on for every account, and how many accounts it is on for')]
class ListFlagsCommand extends Command
{
    public function handle(): int
    {
        $flags = Feature::defined();

        if ($flags === []) {
            $this->info('No flag is declared.');

            return self::SUCCESS;
        }

        $accounts = Account::query()->count();

        foreach ($flags as $flag) {
            $state = Feature::for(null)->active($flag) ? 'on' : 'off';
            $accountsOn = FlagState::accountsOn($flag);

            $this->line("{$flag}: {$state} for every account, on for {$accountsOn} of {$accounts} accounts");
        }

        $this->info('Flags declared: '.count($flags).'.');

        return self::SUCCESS;
    }
}
