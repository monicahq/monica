<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\LifeEvent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('life_event_participants', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(Account::class)->comment('account the participation belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(LifeEvent::class)->comment('life event taken part in')->constrained()->cascadeOnDelete();
            $table->string('participant_type')->comment('class of the participant, a contact or an entity');
            $table->unsignedBigInteger('participant_id')->comment('id of that record');
            $table->string('role')->nullable()->comment('what they were there as, such as host, guest or practitioner');
            $table->string('notes')->nullable()->comment('anything specific to this participant’s presence');
            $table->unsignedInteger('position')->default(0)->comment('order participants are listed in');
            $table->timestamps();

            $table->unique(['life_event_id', 'participant_type', 'participant_id'], 'life_event_participants_unique');
            $table->index(['participant_type', 'participant_id', 'life_event_id'], 'life_event_participants_participant_index');
        });
    }
};
