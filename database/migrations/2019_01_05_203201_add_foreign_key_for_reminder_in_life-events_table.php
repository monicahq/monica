<?php


use App\Models\Contact\Reminder;
use App\Models\Contact\LifeEvent;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeyForReminderInLifeEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        LifeEvent::select('reminder_id')
            ->whereNotNull('reminder_id')
            ->chunk(50, function ($lifeEvents) {
                foreach ($lifeEvents as $lifeEvent) {
                    try {
                        Reminder::findOrFail($lifeEvent->reminder_id);
                    } catch (ModelNotFoundException $e) {
                        $lifeEvent->reminder_id = null;
                        $lifeEvent->save();
                        continue;
                    }
                }
            });

        Schema::disableForeignKeyConstraints();
        Schema::table('life_events', function (Blueprint $table) {
            $table->unsignedInteger('reminder_id')->change();
            $table->foreign('reminder_id')->references('id')->on('reminders')->onDelete('set null');
        });
        Schema::enableForeignKeyConstraints();
    }
}
