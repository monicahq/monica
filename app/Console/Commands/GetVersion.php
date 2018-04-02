<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GetVersion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:getversion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get current version of monica';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->line(config('monica.app_version'));
    }
}
