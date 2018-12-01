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
use Illuminate\Console\Application;

class SetupTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:test
                            {--skipSeed : Whether we should populate the database with fake data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the test environment with optional fake data for testing purposes.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! $this->confirm('Are you sure you want to proceed? This will delete ALL data in your environment.')) {
            return;
        }

        $this->artisan('✓ Performing migrations', 'migrate:fresh');

        $this->artisan('✓ Symlink the storage folder', 'storage:link');

        if (! $this->option('skipSeed')) {
            $this->artisan('✓ Filling  database with fake data', 'db:seed', ['--class' => 'FakeContentTableSeeder']);
        }

        $this->line('');
        $this->line('-----------------------------');
        $this->line('|');
        $this->line('| Welcome to Monica v'.config('monica.app_version'));
        $this->line('|');
        $this->line('-----------------------------');
        $this->info('| You can now sign in to your account:');
        $this->line('| username: admin@admin.com');
        $this->line('| password: admin');
        $this->line('| URL:      '.config('app.url'));
        $this->line('-----------------------------');

        $this->info('Setup is done. Have fun.');
    }

    public function exec($message, $command)
    {
        $this->info($message);
        $this->line($command);
        exec($command, $output);
        $this->line(implode('\n', $output));
        $this->line('');
    }

    public function artisan($message, $command, array $arguments = [])
    {
        $this->info($message);
        $this->line(Application::formatCommandString($command));
        $this->callSilent($command, $arguments);
        $this->line('');
    }
}
