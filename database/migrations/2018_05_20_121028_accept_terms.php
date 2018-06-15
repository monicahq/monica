<?php

use App\Models\User\User;
use Illuminate\Database\Migrations\Migration;

class AcceptTerms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        User::chunk(200, function ($users) {
            foreach ($users as $user) {
                \Log::info($user->id);
                if ($user->account) {
                    $user->acceptPolicy();
                }
            }
        });
    }
}
