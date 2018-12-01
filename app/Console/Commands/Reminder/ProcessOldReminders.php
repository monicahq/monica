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


namespace App\Console\Commands\Reminder;

use Illuminate\Console\Command;
use App\Models\Contact\Reminder;

/**
 * Because of an old bug, some reminders have never been sent and sit on the
 * database, with very old `next_expected` date. This command
 * - delete one time reminders
 * - calculate the next expected date of the reminder based on its frequency.
 */
class ProcessOldReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:old_reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedule the next expected date for reminders which have never been sent';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Reminder::where('next_expected_date', '<', now()->subDays(1))
                    ->chunk(500, function ($reminders) {
                        $this->schedule($reminders);
                    });
    }

    private function schedule($reminders)
    {
        foreach ($reminders as $reminder) {
            // Skip the reminder if the contact has been deleted (and for some
            // reasons, the reminder hasn't)
            if (! $reminder->contact) {
                $reminder->delete();
                continue;
            }

            if ($reminder->frequency_type == 'one_time') {
                $reminder->delete();
            }

            if ($reminder->frequency_type != 'one_time') {
                $reminder->calculateNextExpectedDate();
                $reminder->save();
            }
        }
    }
}
