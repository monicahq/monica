<?php

use App\Models\Contact\Task;
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
            echo $task->id.' ';
            if (! is_null($task->title)) {
                $task->title = decrypt($task->title);
            }

            if (! is_null($task->description)) {
                $task->description = decrypt($task->description);
            }

            $task->save();
        }
    }
}
