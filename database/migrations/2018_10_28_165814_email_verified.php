<?php

use App\Models\User\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EmailVerified extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->after('email')->nullable();
        });

        if (Schema::hasColumn('users', 'confirmed')) {
            User::chunk(200, function ($users) {
                foreach ($users as $user) {
                    if ($user->confirmed) {
                        $user->forceFill([
                            'email_verified_at' => $user->created_at,
                        ])->save();
                    }
                }
            });

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('confirmed');
            });

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('confirmation_code');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('confirmed')->default(false);
            $table->string('confirmation_code')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_verified_at');
        });
    }
}
