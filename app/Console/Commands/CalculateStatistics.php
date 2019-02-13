<?php

namespace App\Console\Commands;

use App\Models\Account\Account;
use Illuminate\Console\Command;
use App\Models\Instance\Statistic;
use Illuminate\Support\Facades\DB;

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
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
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
        $statistic->number_of_activities = DB::table('activities')->count();
        $statistic->number_of_addresses = DB::table('addresses')->count();
        $statistic->number_of_api_calls = DB::table('api_usage')->count();
        $statistic->number_of_calls = DB::table('calls')->count();
        $statistic->number_of_contact_fields = DB::table('contact_fields')->count();
        $statistic->number_of_contact_field_types = DB::table('contact_field_types')->count();
        $statistic->number_of_debts = DB::table('debts')->count();
        $statistic->number_of_entries = DB::table('entries')->count();
        $statistic->number_of_gifts = DB::table('gifts')->count();
        $statistic->number_of_oauth_access_tokens = DB::table('oauth_access_tokens')->count();
        $statistic->number_of_oauth_clients = DB::table('oauth_clients')->count();
        $statistic->number_of_relationships = DB::table('relationships')->count();
        $statistic->number_of_subscriptions = DB::table('subscriptions')->count();
        $statistic->number_of_conversations = DB::table('conversations')->count();
        $statistic->number_of_messages = DB::table('messages')->count();

        $statistic->save();
    }
}
