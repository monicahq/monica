<?php

use App\Models\Account\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeysToSubscriptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $subscriptions = DB::table('subscriptions')->get();

        foreach ($subscriptions as $subscription) {
            try {
                Account::findOrFail($subscription->account_id);
            } catch (ModelNotFoundException $e) {
                DB::table('subscriptions')->where('id', $subscription->id)->delete();
                continue;
            }
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }
}
