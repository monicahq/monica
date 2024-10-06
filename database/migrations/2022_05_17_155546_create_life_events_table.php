<?php

use App\Models\Contact;
use App\Models\Currency;
use App\Models\Emotion;
use App\Models\LifeEvent;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\TimelineEvent;
use App\Models\Vault;
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
        Schema::create('life_event_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Vault::class)->constrained()->cascadeOnDelete();
            $table->integer('position')->nullable();
            $table->string('label')->nullable();
            $table->string('label_translation_key')->nullable();
            $table->boolean('can_be_deleted')->default(false);
            $table->timestamps();
        });

        Schema::create('life_event_types', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(LifeEventCategory::class)->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->string('label_translation_key')->nullable();
            $table->boolean('can_be_deleted')->default(false);
            $table->integer('position')->nullable();
            $table->timestamps();
        });

        Schema::create('timeline_events', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Vault::class)->constrained()->cascadeOnDelete();
            $table->date('started_at');
            $table->string('label')->nullable();
            $table->boolean('collapsed')->default(true);
            $table->timestamps();
        });

        Schema::create('timeline_event_participants', function (Blueprint $table) {
            $table->foreignIdFor(Contact::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(TimelineEvent::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('life_events', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TimelineEvent::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(LifeEventType::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Emotion::class)->nullable()->constrained()->nullOnDelete();
            $table->date('happened_at');
            $table->boolean('collapsed')->default(false);
            $table->string('summary')->nullable();
            $table->text('description')->nullable();
            $table->integer('costs')->nullable();
            $table->foreignIdFor(Currency::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Contact::class, 'paid_by_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->integer('duration_in_minutes')->nullable();
            $table->integer('distance')->nullable();
            $table->char('distance_unit', 2)->nullable();
            $table->string('from_place')->nullable();
            $table->string('to_place')->nullable();
            $table->string('place')->nullable();
            $table->timestamps();
        });

        Schema::create('life_event_participants', function (Blueprint $table) {
            $table->foreignIdFor(Contact::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(LifeEvent::class)->constrained()->cascadeOnDelete();
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
        Schema::dropIfExists('life_event_participants');
        Schema::dropIfExists('life_events');
        Schema::dropIfExists('timeline_event_participants');
        Schema::dropIfExists('timeline_events');
        Schema::dropIfExists('life_event_types');
        Schema::dropIfExists('life_event_categories');
    }
};
