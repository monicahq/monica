<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\LifeEventType;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('life_events', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used in URLs and by the API');
            $table->foreignIdFor(Account::class)->comment('account the life event belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault it lives in')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(LifeEventType::class)->comment('nature of the life event')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(StructureVersion::class)->comment('exact field structure used when it was created')->constrained()->cascadeOnDelete();
            $table->date('happened_on')->comment('the day it happened, or the first day when it spans several');
            $table->date('ended_on')->nullable()->comment('the last day, null when it happened on a single day');
            $table->string('date_precision', 16)->default('day')->comment('how precisely the date is known, a DatePrecisionEnum value');
            $table->time('happened_at_time')->nullable()->comment('time of day, null when it does not matter or is not known');
            $table->unsignedInteger('duration_in_minutes')->nullable()->comment('how long it lasted, null when unknown');
            $table->string('timezone', 64)->nullable()->comment('time zone the time was entered in, so it does not shift when the user moves');
            $table->string('title')->nullable()->comment('short label, null when the type and the participants say enough');
            $table->text('narrative')->nullable()->comment('the free account of what happened');
            $table->string('location')->nullable()->comment('where it happened, in words');
            $table->decimal('latitude', 10, 7)->nullable()->comment('latitude, null when no place was pinned');
            $table->decimal('longitude', 10, 7)->nullable()->comment('longitude, null the same way');
            $table->boolean('is_shareable')->default(false)->comment('whether a share link may include it at all');
            $table->boolean('narrative_is_shareable')->default(false)->comment('whether the free account may be included');
            $table->string('created_from', 16)->default('contact')->comment('which entry path produced it, a LifeEventSourceEnum value');
            $table->foreignIdFor(User::class, 'created_by_user_id')->nullable()->comment('user who entered it, null when an import did')->constrained()->nullOnDelete();
            $table->unsignedInteger('participant_count')->default(0)->comment('running count of participants');
            $table->text('search_name')->nullable()->comment('the title the life event is listed under, as the database search engine reads it');
            $table->mediumText('search_body')->nullable()->comment('the narrative, the location and every value that is not sensitive, for the database search engine');
            $table->mediumText('search_sensitive_body')->nullable()->comment('every sensitive value, for the database search engine, searched only for a caller who may read them');
            $table->text('search_linked_names')->nullable()->comment('the names of the participants, for the database search engine');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vault_id', 'happened_on']);

            if (config('scout.full_text_index') && in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb', 'pgsql'], true)) {
                $table->fullText(['search_name', 'search_body', 'search_sensitive_body', 'search_linked_names'], 'life_events_search_fulltext')->language('simple');
                $table->fullText(['search_name', 'search_body', 'search_linked_names'], 'life_events_search_without_sensitive_fulltext')->language('simple');
            }
        });
    }
};
