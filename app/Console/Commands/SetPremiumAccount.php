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

use App\Models\Account\Account;
use Illuminate\Console\Command;

class SetPremiumAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'account:setpremium {accountId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Give a premium access to an account.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $account = Account::findOrFail($this->argument('accountId'));
        $account->has_access_to_paid_version_for_free = 1;
        $account->save();
    }
}
