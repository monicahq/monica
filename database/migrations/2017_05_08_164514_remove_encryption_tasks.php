<?php

use App\Task;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveEncryptionTasks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tasks = Task::all();
        foreach ($tasks as $task) {
            if (!is_null ($task->title)) {
                $task->title = decrypt($task->title);
            }

            if (!is_null ($task->description)) {
                $task->description = decrypt($task->description);
            }

            $task->save();
        }
    }
}