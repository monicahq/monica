<?php

use App\Models\User\Module;
use App\Models\Account\Account;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
