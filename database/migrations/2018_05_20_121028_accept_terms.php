<?php

use App\Models\User\User;
use App\Services\User\AcceptPolicy;
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
                if ($user->account) {
                    app(AcceptPolicy::class)->execute([
                        'account_id' => $user->account_id,
                        'user_id' => $user->id,
                        'ip_address' => null,
                    ]);
                }
            }
        });
    }
}
