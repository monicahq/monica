<?php

use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Gift;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Account\ImportJob;
use App\Models\User\User;

class AddForeignKeysToImportJobs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to parse the import job table to make sure that we don't have
        // "ghost" import job that are not associated with any account
        ImportJob::chunk(200, function ($importJobs) {
            foreach ($importJobs as $importJob) {
                try {
                    Account::findOrFail($importJob->account_id);
                    User::findOrFail($importJob->user_id);
                } catch (ModelNotFoundException $e) {
                    $importJob->delete();
                    continue;
                }
            }
        });

        Schema::table('import_jobs', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('user_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
}
