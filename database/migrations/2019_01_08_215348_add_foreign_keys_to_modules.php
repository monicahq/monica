<?php

use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Gift;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Account\ImportJob;
use App\Models\Account\ImportJobReport;
use App\Models\User\User;
use App\Models\Account\Invitation;
use App\Models\Journal\JournalEntry;
use App\Models\User\Module;

class AddForeignKeysToModules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to parse the modules table to make sure that we don't have
        // "ghost" modules that are not associated with any account
        Module::chunk(200, function ($modules) {
            foreach ($modules as $module) {
                try {
                    Account::findOrFail($module->account_id);
                } catch (ModelNotFoundException $e) {
                    $module->delete();
                    continue;
                }
            }
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }
}
