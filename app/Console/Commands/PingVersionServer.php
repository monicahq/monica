<?php

namespace App\Console\Commands;

use App\Contact;
use App\Instance;
use Illuminate\Console\Command;

class PingVersionServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:ping';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ping version.monicahq.com to know if a new version is available';

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
        $instance = Instance::first();

        // Prepare the json to query version.monicahq.com
        $json = [
            'uuid' => $instance->uuid,
            'version' => $instance->current_version,
            'contacts' => Contact::all()->count()
        ];

        dd($json);
    }
}
