<?php

use App\Models\User\User;
use Illuminate\Database\Migrations\Migration;

class RemoveBernTimezone extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $users = User::all();
        foreach ($users as $user) {
            if ($user->timezone == 'Europe/Bern') {
                $user->timezone = 'Europe/Berlin';
                $user->save();
            }
        }
    }
}
