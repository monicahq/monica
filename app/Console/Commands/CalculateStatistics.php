<?php

namespace App\Console\Commands;

use DB;
use App\Account;
use App\Statistic;
use Illuminate\Console\Command;

class CalculateStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:calculatestatistics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate general usage statistics';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $statistic = new Statistic;
        $statistic->number_of_users = DB::table('users')->count();
        $statistic->number_of_contacts = DB::table('contacts')->count();
        $statistic->number_of_notes = DB::table('notes')->count();
        $statistic->number_of_reminders = DB::table('reminders')->count();
        $statistic->number_of_tasks = DB::table('tasks')->count();
        $statistic->number_of_invitations_sent = DB::table('accounts')->sum('number_of_invitations_sent');

        // number_of_accounts_with_more_than_one_user
        $number_of_accounts_with_more_than_one_user = 0;
        foreach (Account::all() as $account) {
            if ($account->users()->count() > 1) {
                $number_of_accounts_with_more_than_one_user = $number_of_accounts_with_more_than_one_user + 1;
            }
        }
        $statistic->number_of_accounts_with_more_than_one_user = $number_of_accounts_with_more_than_one_user;
        $statistic->number_of_import_jobs = DB::table('import_jobs')->count();
        $statistic->number_of_tags = DB::table('tags')->count();
        $statistic->save();
    }
}
