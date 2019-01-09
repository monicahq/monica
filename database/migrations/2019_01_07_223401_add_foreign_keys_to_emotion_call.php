<?php

use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Debt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeysToEmotionCall extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('emotion_call', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->unsignedInteger('emotion_id')->change();
            $table->unsignedInteger('call_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('emotion_id')->references('id')->on('emotions')->onDelete('cascade');
            $table->foreign('call_id')->references('id')->on('calls')->onDelete('cascade');
        });
    }
}
