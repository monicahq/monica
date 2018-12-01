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


namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Models\Account\ImportJob;
use App\Services\VCard\ImportVCard;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AddContactFromVCard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $importJob;
    protected $behaviour;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ImportJob $importJob, $behaviour = ImportVCard::BEHAVIOUR_ADD)
    {
        $this->importJob = $importJob;
        $this->behaviour = $behaviour;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->importJob->process($this->behaviour);
    }
}
