<?php

namespace App\Console\Commands;

use App\Models\Contact\Contact;
use Illuminate\Console\Command;
use App\Jobs\StayInTouch\ScheduleStayInTouch;

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
        // we had two days to make sure we cover all timezones
        $contacts = Contact::where('stay_in_touch_trigger_date', '<', now()->addDays(2))
                                ->orderBy('stay_in_touch_trigger_date', 'asc')->get();

        foreach ($contacts as $contact) {
            ScheduleStayInTouch::dispatch($contact);
        }
    }
}
