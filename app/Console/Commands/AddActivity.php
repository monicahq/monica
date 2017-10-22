<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\ActivityTypeGroup;
use InvalidArgumentException;
use Illuminate\Support\Facades\DB;

class AddActivity extends MonicaCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:add-activity {name} {location_type} {icon} {group}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new activity';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');
        $location_type = $this->argument('location_type');
        $icon = $this->argument('icon');
        $group = $this->argument('group');

        $groupType = ActivityTypeGroup::where('key', '=', $group)->first();

        if (! $groupType) {
            throw new InvalidArgumentException("Unknown group type '".$group."'");
        }

        DB::table('activity_types')->insert([
            'key' => $name,
            'location_type' => $location_type,
            'icon' => $icon,
            'activity_type_group_id' => $groupType->id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->info("Created activity type '".$name."'");
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required',
            'location_type' => 'required|in:his_place,my_place,outside',
            'icon' => 'required|in:ate_his_place,ate_home,bar,concert,hang_out,movie_home,museum,picknicked,play,restaurant,sport,talk_home,theater',
            'group' => 'required',
        ];
    }
}
