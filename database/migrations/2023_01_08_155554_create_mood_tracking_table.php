<?php

use App\Models\Contact;
use App\Models\MoodTrackingParameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mood_tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Contact::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(MoodTrackingParameter::class)->constrained()->cascadeOnDelete();
            $table->datetime('rated_at');
            $table->text('note')->nullable();
            $table->integer('number_of_hours_slept')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mood_tracking_events');
    }
};
