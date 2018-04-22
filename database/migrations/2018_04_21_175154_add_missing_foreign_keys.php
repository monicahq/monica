<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activities', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('activity_type_id')->references('id')->on('activity_types');
        });
        Schema::table('activity_statistics', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
        Schema::table('activity_types', function(Blueprint $table) {
            $table->foreign('activity_type_group_id')->references('id')->on('activity_type_groups');
        });
        Schema::table('calls', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
        Schema::table('changelog_user', function(Blueprint $table) {
            $table->foreign('changelog_id')->references('id')->on('changelogs');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        Schema::table('contact_tag', function(Blueprint $table) {
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
        Schema::table('contacts', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('gender_id')->references('id')->on('genders');
            $table->foreign('deceased_special_date_id')->references('id')->on('special_dates');
            $table->foreign('birthday_special_date_id')->references('id')->on('special_dates');
            $table->foreign('first_met_through_contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('first_met_special_date_id')->references('id')->on('special_dates');
        });
        Schema::table('days', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
        Schema::table('debts', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
        Schema::table('default_relationship_types', function(Blueprint $table) {
            $table->foreign('relationship_type_group_id')->references('id')->on('default_relationship_type_groups');
        });
        Schema::table('entries', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
        Schema::table('events', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
        Schema::table('genders', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
        Schema::table('gifts', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
        Schema::table('import_job_reports', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('import_job_id')->references('id')->on('import_jobs');
        });
        Schema::table('import_jobs', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        Schema::table('invitations', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('invited_by_user_id')->references('id')->on('users')->onDelete('cascade');
        });
        Schema::table('journal_entries', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
        Schema::table('modules', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
        Schema::table('notes', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
        Schema::table('notifications', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('reminder_id')->references('id')->on('reminders');
        });
        Schema::table('oauth_access_tokens', function(Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('oauth_clients')->onDelete('cascade');
        });
        Schema::table('oauth_auth_codes', function(Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('oauth_clients')->onDelete('cascade');
        });
        Schema::table('oauth_clients', function(Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        Schema::table('oauth_personal_access_clients', function(Blueprint $table) {
            $table->foreign('client_id')->references('id')->on('oauth_clients')->onDelete('cascade');
        });
        Schema::table('pets', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('pet_category_id')->references('id')->on('pet_categories');
        });
        Schema::table('relationship_type_groups', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
        Schema::table('relationship_types', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('relationship_type_group_id')->references('id')->on('relationship_type_groups');
        });
        Schema::table('relationships', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('relationship_type_id')->references('id')->on('relationship_types');
        });
        Schema::table('reminder_rules', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
        Schema::table('reminders', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('special_date_id')->references('id')->on('special_dates');
        });
        Schema::table('reminders_sent', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('reminder_id')->references('id')->on('reminders');
        });
        Schema::table('sessions', function(Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
        });
        Schema::table('special_dates', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('reminder_id')->references('id')->on('reminders');
        });
        Schema::table('subscriptions', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
        Schema::table('tags', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
        Schema::table('tasks', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
        Schema::table('users', function(Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->foreign('invited_by_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('activities', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['activity_type_id']);
        });
        Schema::table('activity_statistics', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['contact_id']);
        });
        Schema::table('activity_types', function(Blueprint $table) {
            $table->dropForeign(['activity_type_group_id']);
        });
        Schema::table('calls', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['contact_id']);
        });
        Schema::table('changelog_user', function(Blueprint $table) {
            $table->dropForeign(['changelog_id']);
            $table->dropForeign(['user_id']);
        });
        Schema::table('contact_tag', function(Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropForeign(['tag_id']);
            $table->dropForeign(['account_id']);
        });
        Schema::table('contacts', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['gender_id']);
            $table->dropForeign(['deceased_special_date_id']);
            $table->dropForeign(['birthday_special_date_id']);
            $table->dropForeign(['first_met_through_contact_id']);
            $table->dropForeign(['first_met_special_date_id']);
        });
        Schema::table('days', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
        });
        Schema::table('debts', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['contact_id']);
        });
        Schema::table('default_relationship_types', function(Blueprint $table) {
            $table->dropForeign(['relationship_type_group_id']);
        });
        Schema::table('entries', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
        });
        Schema::table('events', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['contact_id']);
        });
        Schema::table('genders', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
        });
        Schema::table('gifts', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['contact_id']);
        });
        Schema::table('import_job_reports', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['import_job_id']);
        });
        Schema::table('import_jobs', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['user_id']);
        });
        Schema::table('invitations', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['invited_by_user_id']);
        });
        Schema::table('journal_entries', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
        });
        Schema::table('modules', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
        });
        Schema::table('notes', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['contact_id']);
        });
        Schema::table('notifications', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['contact_id']);
            $table->dropForeign(['reminder_id']);
        });
        Schema::table('oauth_access_tokens', function(Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['client_id']);
        });
        Schema::table('oauth_auth_codes', function(Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['client_id']);
        });
        Schema::table('oauth_clients', function(Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('oauth_personal_access_clients', function(Blueprint $table) {
            $table->dropForeign(['client_id']);
        });
        Schema::table('pets', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['contact_id']);
            $table->dropForeign(['pet_category_id']);
        });
        Schema::table('relationship_type_groups', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
        });
        Schema::table('relationship_types', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['relationship_type_group_id']);
        });
        Schema::table('relationships', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['relationship_type_id']);
        });
        Schema::table('reminder_rules', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
        });
        Schema::table('reminders', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['contact_id']);
            $table->dropForeign(['special_date_id']);
        });
        Schema::table('reminders_sent', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['contact_id']);
            $table->dropForeign(['reminder_id']);
        });
        Schema::table('sessions', function(Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('special_dates', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['contact_id']);
            $table->dropForeign(['reminder_id']);
        });
        Schema::table('subscriptions', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
        });
        Schema::table('tags', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
        });
        Schema::table('tasks', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['contact_id']);
        });
        Schema::table('users', function(Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['currency_id']);
            $table->dropForeign(['invited_by_user_id']);
        });
    }
}
