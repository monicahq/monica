<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AddActivityGroup extends MonicaCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:add-activity-group {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new activity group';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');

        DB::table('activity_type_groups')->insert([
            'key' => $name,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->info("Created activity group '".$name."'");
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required',
        ];
    }
}
