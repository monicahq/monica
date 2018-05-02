<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NormalizeForeignKeyColumnsToUnsignedInteger extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
        });
        Schema::table('activity_statistics', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
        });
        Schema::table('activity_types', function (Blueprint $table) {
            $table->unsignedInteger('activity_type_group_id')->change();
        });
        Schema::table('calls', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
        });
        Schema::table('changelog_user', function (Blueprint $table) {
            $table->unsignedInteger('changelog_id')->change();
            $table->unsignedInteger('user_id')->change();
        });
        Schema::table('contact_tag', function (Blueprint $table) {
            $table->unsignedInteger('contact_id')->change();
            $table->unsignedInteger('tag_id')->change();
            $table->unsignedInteger('account_id')->change();
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('gender_id')->change();
            $table->unsignedInteger('deceased_special_date_id')->change();
            $table->unsignedInteger('birthday_special_date_id')->change();
            $table->unsignedInteger('first_met_through_contact_id')->change();
            $table->unsignedInteger('first_met_special_date_id')->change();
        });
        Schema::table('days', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
        });
        Schema::table('debts', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
        });
        Schema::table('default_relationship_types', function (Blueprint $table) {
            $table->unsignedInteger('relationship_type_group_id')->change();
        });
        Schema::table('entries', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
        });
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->unsignedInteger('object_id')->change();
        });
        Schema::table('genders', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
        });
        Schema::table('gifts', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
        });
        Schema::table('import_job_reports', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('user_id')->change();
            $table->unsignedInteger('import_job_id')->change();
        });
        Schema::table('import_jobs', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('user_id')->change();
        });
        Schema::table('invitations', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('invited_by_user_id')->change();
        });
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('journalable_id')->change();
        });
        Schema::table('modules', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
        });
        Schema::table('notes', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
        });
        Schema::table('notifications', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->unsignedInteger('reminder_id')->change();
        });
        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->change();
            $table->unsignedInteger('client_id')->change();
        });
        Schema::table('oauth_auth_codes', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->change();
            $table->unsignedInteger('client_id')->change();
        });
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->change();
        });
        Schema::table('oauth_personal_access_clients', function (Blueprint $table) {
            $table->unsignedInteger('client_id')->change();
        });
        Schema::table('pets', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->unsignedInteger('pet_category_id')->change();
        });
        Schema::table('relationship_type_groups', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
        });
        Schema::table('relationship_types', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('relationship_type_group_id')->change();
        });
        Schema::table('relationships', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('relationship_type_id')->change();
        });
        Schema::table('reminder_rules', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
        });
        Schema::table('reminders', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->unsignedInteger('special_date_id')->change();
        });
        Schema::table('reminders_sent', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->unsignedInteger('reminder_id')->change();
        });
        Schema::table('sessions', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->change();
        });
        Schema::table('special_dates', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->unsignedInteger('reminder_id')->change();
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
        });
        Schema::table('tags', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
        });
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('currency_id')->change();
            $table->unsignedInteger('invited_by_user_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->integer('account_id')->change();
        });
        Schema::table('activity_statistics', function (Blueprint $table) {
            $table->integer('account_id')->change();
            $table->integer('contact_id')->change();
        });
        Schema::table('activity_types', function (Blueprint $table) {
            $table->integer('activity_type_group_id')->change();
        });
        Schema::table('calls', function (Blueprint $table) {
            $table->integer('account_id')->change();
            $table->integer('contact_id')->change();
        });
        Schema::table('changelog_user', function (Blueprint $table) {
            $table->integer('changelog_id')->change();
            $table->integer('user_id')->change();
        });
        Schema::table('contact_tag', function (Blueprint $table) {
            $table->integer('contact_id')->change();
            $table->integer('tag_id')->change();
            $table->integer('account_id')->change();
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->integer('account_id')->change();
            $table->integer('gender_id')->change();
            $table->integer('deceased_special_date_id')->change();
            $table->integer('birthday_special_date_id')->change();
            $table->integer('first_met_through_contact_id')->change();
            $table->integer('first_met_special_date_id')->change();
        });
        Schema::table('days', function (Blueprint $table) {
            $table->integer('account_id')->change();
        });
        Schema::table('debts', function (Blueprint $table) {
            $table->integer('account_id')->change();
            $table->integer('contact_id')->change();
        });
        Schema::table('default_relationship_types', function (Blueprint $table) {
            $table->integer('relationship_type_group_id')->change();
        });
        Schema::table('entries', function (Blueprint $table) {
            $table->integer('account_id')->change();
        });
        Schema::table('events', function (Blueprint $table) {
            $table->integer('account_id')->change();
            $table->integer('contact_id')->change();
            $table->integer('object_id')->change();
        });
        Schema::table('genders', function (Blueprint $table) {
            $table->integer('account_id')->change();
        });
        Schema::table('gifts', function (Blueprint $table) {
            $table->integer('account_id')->change();
            $table->integer('contact_id')->change();
        });
        Schema::table('import_job_reports', function (Blueprint $table) {
            $table->integer('account_id')->change();
            $table->integer('user_id')->change();
            $table->integer('import_job_id')->change();
        });
        Schema::table('import_jobs', function (Blueprint $table) {
            $table->integer('account_id')->change();
            $table->integer('user_id')->change();
        });
        Schema::table('invitations', function (Blueprint $table) {
            $table->integer('account_id')->change();
            $table->integer('invited_by_user_id')->change();
        });
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->integer('account_id')->change();
            $table->integer('journalable_id')->change();
        });
        Schema::table('modules', function (Blueprint $table) {
            $table->integer('account_id')->change();
        });
        Schema::table('notes', function (Blueprint $table) {
            $table->integer('account_id')->change();
            $table->integer('contact_id')->change();
        });
        Schema::table('notifications', function (Blueprint $table) {
            $table->integer('account_id')->change();
            $table->integer('contact_id')->change();
            $table->integer('reminder_id')->change();
        });
        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->integer('user_id')->change();
            $table->integer('client_id')->change();
        });
        Schema::table('oauth_auth_codes', function (Blueprint $table) {
            $table->integer('user_id')->change();
            $table->integer('client_id')->change();
        });
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->integer('user_id')->change();
        });
        Schema::table('oauth_personal_access_clients', function (Blueprint $table) {
            $table->integer('client_id')->change();
        });
        Schema::table('pets', function (Blueprint $table) {
            $table->integer('account_id')->change();
            $table->integer('contact_id')->change();
            $table->integer('pet_category_id')->change();
        });
        Schema::table('relationship_type_groups', function (Blueprint $table) {
            $table->integer('account_id')->change();
        });
        Schema::table('relationship_types', function (Blueprint $table) {
            $table->integer('account_id')->change();
            $table->integer('relationship_type_group_id')->change();
        });
        Schema::table('relationships', function (Blueprint $table) {
            $table->integer('account_id')->change();
            $table->integer('relationship_type_id')->change();
        });
        Schema::table('reminder_rules', function (Blueprint $table) {
            $table->integer('account_id')->change();
        });
        Schema::table('reminders', function (Blueprint $table) {
            $table->integer('account_id')->change();
            $table->integer('contact_id')->change();
            $table->integer('special_date_id')->change();
        });
        Schema::table('reminders_sent', function (Blueprint $table) {
            $table->integer('account_id')->change();
            $table->integer('contact_id')->change();
            $table->integer('reminder_id')->change();
        });
        Schema::table('sessions', function (Blueprint $table) {
            $table->integer('user_id')->change();
        });
        Schema::table('special_dates', function (Blueprint $table) {
            $table->integer('account_id')->change();
            $table->integer('contact_id')->change();
            $table->integer('reminder_id')->change();
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->integer('account_id')->change();
        });
        Schema::table('tags', function (Blueprint $table) {
            $table->integer('account_id')->change();
        });
        Schema::table('tasks', function (Blueprint $table) {
            $table->integer('account_id')->change();
            $table->integer('contact_id')->change();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->integer('account_id')->change();
            $table->integer('currency_id')->change();
            $table->integer('invited_by_user_id')->change();
        });
    }
}
