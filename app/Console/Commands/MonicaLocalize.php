<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MonicaLocalize extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:localize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate locale files for Monica.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $locales = config('localizer.supported-locales');
        array_shift($locales);
        $this->call('localize', ['lang' => implode(',', $locales)]);
    }
}
