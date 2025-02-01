<?php

use App\Models\Contact;
use App\Models\Goal;
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
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Contact::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('active')->default(false);
            $table->timestamps();
        });

        Schema::create('streaks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Goal::class)->constrained()->cascadeOnDelete();
            $table->datetime('happened_at');
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
        Schema::dropIfExists('streaks');
        Schema::dropIfExists('goals');
    }
};
