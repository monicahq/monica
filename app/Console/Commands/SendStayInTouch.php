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
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        // we add two days to make sure we cover all timezones
        Contact::where('stay_in_touch_trigger_date', '<', now()->addDays(2))
                ->whereNotNull('stay_in_touch_frequency')
                ->orderBy('stay_in_touch_trigger_date', 'asc')
                ->chunk(500, function ($contacts) {
                    $this->schedule($contacts);
                });
    }

    private function schedule($contacts)
    {
        foreach ($contacts as $contact) {
            ScheduleStayInTouch::dispatch($contact);
        }
    }
}
