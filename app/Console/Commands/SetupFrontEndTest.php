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

use App\Models\Account\Account;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Console\Commands\Helpers\CommandExecutor;
use App\Console\Commands\Helpers\CommandExecutorInterface;

class SetupFrontEndTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:frontendtesting';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the test environment exclusively for front-end testing with Cypress.';

    private $dumpfile = 'monicadump.sql';

    private $account;

    /**
     * The Command Executor.
     *
     * @var CommandExecutorInterface
     */
    public $commandExecutor;

    /**
     * Create a new command.
     *
     * @param CommandExecutorInterface
     */
    public function __construct()
    {
        $this->commandExecutor = new CommandExecutor($this);
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $connection = DB::connection();
        if (file_exists('monicadump.sql')) {
            $cmd = 'mysql -u '.$connection->getConfig('username');
            if ($connection->getConfig('password') != '') {
                $cmd .= ' -p'.$connection->getConfig('password');
            }
            if ($connection->getConfig('host') != '') {
                $cmd .= ' -h '.$connection->getConfig('host');
            }
            if ($connection->getConfig('port') != '') {
                $cmd .= ' -P '.$connection->getConfig('port');
            }
            $cmd .= ' '.$connection->getDatabaseName();
            $cmd .= ' < '.$this->dumpfile;
            $this->commandExecutor->exec('mysql import ...', $cmd);
        } else {
            $this->commandExecutor->artisan('perform fresh migration', 'migrate:fresh');
        }
        $this->info('Create account');
        $this->account = Account::createDefault('John', 'Doe', 'admin@admin.com', 'admin');

        // get first user
        $this->info('Fix first user');
        $user = $this->account->users()->first();
        $user->markEmailAsVerified();
    }
}
