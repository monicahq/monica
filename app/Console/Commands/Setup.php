<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Setup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:production';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform setup of Monica';

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
        $this->call('migrate', ['--force' => true,]);
        $this->call('db:seed', ['--class' => 'ActivityTypesTableSeeder', '--force' => true]);
        $this->call('db:seed', ['--class' => 'CountriesSeederTable', '--force' => true]);
        $this->call('storage:link');
    }
}
