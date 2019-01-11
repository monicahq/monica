<?php

use App\Models\Contact\Tag;
use App\Models\Account\Account;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeysToTags extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to parse the tags table to make sure that we don't have
        // "ghost" tags that are not associated with any account
        Tag::chunk(200, function ($tags) {
            foreach ($tags as $tag) {
                try {
                    Account::findOrFail($tag->account_id);
                } catch (ModelNotFoundException $e) {
                    $tag->delete();
                    continue;
                }
            }
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }
}
