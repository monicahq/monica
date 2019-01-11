<?php

use App\Models\User\User;
use App\Models\Settings\Term;
use App\Models\Account\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeysToTermUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $terms = DB::table('term_user')->get();

        foreach ($terms as $term) {
            try {
                Account::findOrFail($term->account_id);
                User::findOrFail($term->user_id);
                Term::findOrFail($term->term_id);
            } catch (ModelNotFoundException $e) {
                DB::table('term_user')->where('id', $term->id)->delete();
                continue;
            }
        }

        Schema::table('term_user', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('user_id')->change();
            $table->unsignedInteger('term_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('term_id')->references('id')->on('terms')->onDelete('cascade');
        });
    }
}
