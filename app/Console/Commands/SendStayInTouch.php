<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendStayInTouch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:stay_in_touch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications about staying in touch with contacts';

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
        $contacts = Contact::where('stay_in_touch_trigger_date', '<', now()->addDays(2))
                                ->orderBy('stay_in_touch_trigger_date', 'asc')->get();
    }
}
