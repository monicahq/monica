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

use Illuminate\Console\Command;
use App\Models\Contact\Notification;
use App\Jobs\Notification\ScheduleNotification;

class SendNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications about reminders';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Notification::where('trigger_date', '<', now()->addDays(2))
                    ->orderBy('trigger_date', 'asc')
                    ->chunk(500, function ($notifications) {
                        $this->schedule($notifications);
                    });
    }

    private function schedule($notifications)
    {
        foreach ($notifications as $notification) {
            if (! $notification->contact) {
                $notification->delete();
                continue;
            }

            ScheduleNotification::dispatch($notification);
        }
    }
}
