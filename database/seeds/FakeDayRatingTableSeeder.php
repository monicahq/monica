<?php

use App\Day;
use Carbon\Carbon;
use App\JournalEntry;
use Illuminate\Database\Seeder;

class FakeDayRatingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = Carbon::now();
        $then = $now->copy()->subYear();

        $this->command->info($now->format('Y-m-d'));
        $this->command->info($then->format('Y-m-d'));

        $dates = [];
        for ($date = $then; $date->lte($now); $date->addDay()) {
            $this->command->info($date->format('Y-m-d'));
            $dates[] = $date->format('Y-m-d');
        }

        // populate account table
        $accountID = DB::table('accounts')->count() ? DB::table('accounts')->first()->id : null;
        if (! $accountID) {
            $accountID = DB::table('accounts')->insertGetId([
                'api_key' => str_random(30),
            ]);
        }

        array_map(function ($date) use ($accountID, $now) {
            $journalEntry = (new JournalEntry)->add(Day::create([
                'account_id' => $accountID,
                'date' => $date,
                'rate' => rand(1, 3),
            ]));
            $journalEntry->update(['date' => $date]);
        }, $dates);
    }
}
