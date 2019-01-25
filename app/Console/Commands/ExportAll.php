<?php

namespace App\Console\Commands;

use App\Jobs\ExportAllAsSQL;
use Illuminate\Console\Command;

class ExportAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports all data as SQL to storage/app';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $job = new ExportAllAsSQL();
        $this->info('Exported as '.$job->handle().'');
    }
}
