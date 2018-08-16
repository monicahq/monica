<?php

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
