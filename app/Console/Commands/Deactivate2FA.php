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

use App\Models\User\User;
use Illuminate\Console\Command;

class Deactivate2FA extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '2fa:deactivate {--email= : The email of the user to deactivate 2FA} {--force : run without asking for confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate 2FA for this user';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // retrieve the email from the option
        $email = $this->option('email');

        // if no email was passed to the option, prompt the user to enter the email
        if (! $email) {
            $email = $this->ask('what is the user\'s email?');
        }

        // retrieve the user with the specified email
        $user = User::where('email', $email)->first();

        if (! $user) {
            // show an error and exist if the user does not exist
            $this->error('No user with that email.');

            return;
        }
        if (is_null($user->google2fa_secret)) {
            // show an error and exist if the user does not exist
            $this->error('2FA is currently not activated for this user.');

            return;
        }

        // Print a warning
        $this->info('2FA will be deactivated for '.$user->email);
        $this->info('This action can\'t be cancelled.');

        // ask for confirmation if not forced
        if (! $this->option('force') && ! $this->confirm('Do you wish to continue?')) {
            return;
        }

        // remove google2fa_secret key
        $user->google2fa_secret = null;

        // save the user
        $user->save();

        // show the new secret key
        $this->info('2FA has been deactivated for '.$user->email);
    }
}
