<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Forklift extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:forklift';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate data from MySQL to PostgreSQL';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->line('test');
    }
}
