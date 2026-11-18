<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('blackbookd_enabled')->default(false)->after('storage_limit_in_mb');
            $table->boolean('blackbookd_blur_dashboard')->default(true)->after('blackbookd_enabled');
            $table->unsignedSmallInteger('blackbookd_partner_lookback_days')->default(90)->after('blackbookd_blur_dashboard');
        });

        Schema::create('blackbookd_encounters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vault_id');
            $table->foreign('vault_id')->references('id')->on('vaults')->cascadeOnDelete();
            $table->dateTime('occurred_at');
            $table->string('location_label')->nullable();
            $table->string('how_met_category')->nullable();
            $table->string('how_met_app')->nullable();
            $table->text('how_met_detail')->nullable();
            $table->json('protection_used')->nullable();
            $table->json('activity_tags')->nullable();
            $table->text('substances')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('private_notes')->nullable();
            $table->text('consent_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('blackbookd_encounter_contact', function (Blueprint $table) {
            $table->uuid('blackbookd_encounter_id');
            $table->uuid('contact_id');
            $table->primary(['blackbookd_encounter_id', 'contact_id']);
            $table->foreign('blackbookd_encounter_id')->references('id')->on('blackbookd_encounters')->cascadeOnDelete();
            $table->foreign('contact_id')->references('id')->on('contacts')->cascadeOnDelete();
        });

        Schema::create('blackbookd_contact_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('contact_id')->unique();
            $table->foreign('contact_id')->references('id')->on('contacts')->cascadeOnDelete();
            $table->string('hiv_status')->nullable();
            $table->boolean('on_prep')->nullable();
            $table->date('last_test_date')->nullable();
            $table->text('kinks')->nullable();
            $table->text('boundaries')->nullable();
            $table->string('relationship_type')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('blackbookd_health_log_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vault_id');
            $table->foreign('vault_id')->references('id')->on('vaults')->cascadeOnDelete();
            $table->string('entry_type');
            $table->date('occurred_on');
            $table->json('payload')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('blackbookd_dating_pipelines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vault_id');
            $table->uuid('contact_id')->unique();
            $table->foreign('vault_id')->references('id')->on('vaults')->cascadeOnDelete();
            $table->foreign('contact_id')->references('id')->on('contacts')->cascadeOnDelete();
            $table->string('handle');
            $table->string('app_source')->nullable();
            $table->unsignedSmallInteger('age')->nullable();
            $table->string('metro_location')->nullable();
            $table->string('role')->nullable();
            $table->string('looking_for')->nullable();
            $table->string('stage')->default('first_contact_draft_ready');
            $table->string('last_message_from')->nullable();
            $table->text('last_message_summary')->nullable();
            $table->dateTime('last_message_at')->nullable();
            $table->text('what_he_said_matters')->nullable();
            $table->text('next_step')->nullable();
            $table->text('red_flags_notes')->nullable();
            $table->string('phone_numbers_exchanged')->default('undecided');
            $table->boolean('boundary_held_do_not_reengage')->default(false);
            $table->unsignedTinyInteger('long_term_fit_score')->nullable();
            $table->text('long_term_fit_reason')->nullable();
            $table->timestamps();

            $table->unique(['vault_id', 'handle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blackbookd_dating_pipelines');
        Schema::dropIfExists('blackbookd_health_log_entries');
        Schema::dropIfExists('blackbookd_contact_profiles');
        Schema::dropIfExists('blackbookd_encounter_contact');
        Schema::dropIfExists('blackbookd_encounters');

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn([
                'blackbookd_enabled',
                'blackbookd_blur_dashboard',
                'blackbookd_partner_lookback_days',
            ]);
        });
    }
};