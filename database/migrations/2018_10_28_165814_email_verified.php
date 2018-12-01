<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



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
