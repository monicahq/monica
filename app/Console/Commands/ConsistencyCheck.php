<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ConsistencyCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'datamigration:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private $sql = 
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
        //
        $this->comment("hello");
    }
}
