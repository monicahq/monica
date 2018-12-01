<?php
/**
  *  This file is part of Monica.
  *
  *  Monica is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  (at your option) any later version.
  *
  *  Monica is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
  **/


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
     * @return mixed
     */
    public function handle()
    {
        // we add two days to make sure we cover all timezones
        Contact::where('stay_in_touch_trigger_date', '<', now()->addDays(2))
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
