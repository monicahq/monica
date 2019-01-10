<?php

use App\Models\User\User;
use App\Models\Account\Account;
use App\Models\Account\ImportJob;
use Illuminate\Support\Facades\Schema;
use App\Models\Account\ImportJobReport;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeysToImportJobReports extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to parse the import job reports table to make sure that we don't have
        // "ghost" import job reports that are not associated with any account
        ImportJobReport::chunk(200, function ($importJobReports) {
            foreach ($importJobReports as $importJobReport) {
                try {
                    Account::findOrFail($importJobReport->account_id);
                    User::findOrFail($importJobReport->user_id);
                    ImportJob::findOrFail($importJobReport->import_job_id);
                } catch (ModelNotFoundException $e) {
                    $importJobReport->delete();
                    continue;
                }
            }
        });

        Schema::table('import_job_reports', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('user_id')->change();
            $table->unsignedInteger('import_job_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('import_job_id')->references('id')->on('import_jobs')->onDelete('cascade');
        });
    }
}
