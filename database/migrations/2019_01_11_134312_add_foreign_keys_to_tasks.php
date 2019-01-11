<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Contact\Task;
use App\Models\Account\Account;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Contact\Contact;

class AddForeignKeysToTasks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to parse the tasks table to make sure that we don't have
        // "ghost" tasks that are not associated with any account
        Task::chunk(200, function ($tasks) {
            foreach ($tasks as $task) {
                try {
                    Account::findOrFail($task->account_id);
                    Contact::findOrFail($task->contact_id);
                } catch (ModelNotFoundException $e) {
                    $task->delete();
                    continue;
                }
            }
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
        });
    }
}
