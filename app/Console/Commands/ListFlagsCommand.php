<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Laravel\Pennant\Feature;

#[Signature('monica:list-flags')]
#[Description('List the flags this instance declares and whether each is on for every account')]
class ListFlagsCommand extends Command
{
    public function handle(): int
    {
        $flags = Feature::defined();

        if ($flags === []) {
            $this->info('No flag is declared.');

            return self::SUCCESS;
        }

        foreach ($flags as $flag) {
            $state = Feature::for(null)->active($flag) ? 'on' : 'off';

            $this->line("{$flag}: {$state} for every account");
        }

        $this->info('Flags declared: '.count($flags).'.');

        return self::SUCCESS;
    }
}
