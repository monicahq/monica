<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Contact\Tag;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeysToContactTag extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $contactTags = DB::table('contact_tag')->get();

        foreach ($contactTags as $contactTag) {
            try {
                Account::findOrFail($contactTag->account_id);
                Tag::findOrFail($contactTag->tag_id);
                Contact::findOrFail($contactTag->contact_id);
            } catch (ModelNotFoundException $e) {
                DB::table('contact_tag')->where('id', $contactTag->id)->delete();
                continue;
            }
        }

        Schema::table('contact_tag', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('tag_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }
}
