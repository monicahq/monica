<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\Avatars\UpdateAllGravatars;

class UpdateGravatars extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:updategravatars';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all gravatars';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        UpdateAllGravatars::dispatch();
    }
}
