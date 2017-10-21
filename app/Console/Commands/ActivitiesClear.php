<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;

class ActivitiesClear extends MonicaCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:clear-activities {--confirm}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all existing activities and activity groups from your installation';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // If they added the confirm flag, we assume they know what they're doing
        if (! $this->option('confirm')) {
            // Do we really want to do this?
            if (! $this->confirm('This will delete all contact activities and activity types. Are you sure you want to do this?')) {
                return;
            }

            // Give them 10 seconds to think about it
            $this->countDownFrom(10);
        }

        // Empty tables
        DB::table('activities')->delete();
        DB::table('activity_types')->delete();
        DB::table('activity_type_groups')->delete();

        $this->info('All activity entries deleted');
    }
}
