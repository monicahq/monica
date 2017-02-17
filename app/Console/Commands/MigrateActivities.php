<?php

namespace App\Console\Commands;

use DB;
use App\Note;
use App\Activity;
use Illuminate\Console\Command;

class MigrateActivities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:migrateactivities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'One time use only';

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
        $notes = Note::where('type', 'activity')
                        ->where('deleted_at', null)
                        ->get();

        foreach ($notes as $note) {
            $activity = new Activity;
            $activity->account_id = $note->account_id;
            $activity->people_id = $note->people_id;
            $activity->activity_type_id = $note->activity_type_id;
            $activity->description = $note->body;
            $activity->date_it_happened = $note->activity_date;
            $activity->user_id_of_the_writer = $note->author_id;
            $activity->created_at = $note->created_at;
            $activity->updated_at = $note->updated_at;
            $activity->save();
        }

        foreach ($notes as $note) {
            $note->forceDelete();
        }
    }
}
