<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class UpdateTimestampsTimezone extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $timezone = env('APP_DEFAULT_TIMEZONE', 'UTC');

        if ($timezone == null || $timezone == 'UTC') {
            return;
        }

        $this->update('accounts', $timezone);
        $this->update('activities', $timezone);
        $this->update('activity_statistics', $timezone);
        $this->update('activity_types', $timezone);
        $this->update('activity_type_categories', $timezone);
        $this->update('addresses', $timezone);
        $this->update('api_usage', $timezone);
        $this->update('calls', $timezone);
        $this->update('changelogs', $timezone);
        $this->update('changelog_user', $timezone, 'changelog_id');
        DB::table('contacts')->chunkById(200, function ($models) use ($timezone) {
            foreach ($models as $model) {
                $created = is_null($model->created_at) ? null : Carbon::createFromTimeString($model->created_at, $timezone)->setTimezone('UTC');
                $updated = is_null($model->updated_at) ? null : Carbon::createFromTimeString($model->updated_at, $timezone)->setTimezone('UTC');
                $last_consulted_at = is_null($model->last_consulted_at) ? null : Carbon::createFromTimeString($model->last_consulted_at, $timezone)->setTimezone('UTC');

                DB::table('contacts')->where('id', $model->id)
                    ->update([
                        'created_at' => $created,
                        'updated_at' => $updated,
                        'last_consulted_at' => $last_consulted_at,
                    ]);
            }
        });
        $this->update('contact_fields', $timezone);
        $this->update('contact_field_types', $timezone);
        $this->update('contact_tag', $timezone, 'contact_id');
        $this->update('currencies', $timezone);
        $this->update('days', $timezone);
        $this->update('debts', $timezone);
        $this->update('default_contact_field_types', $timezone);
        $this->update('default_contact_modules', $timezone);
        $this->update('default_activity_types', $timezone);
        $this->update('default_activity_type_categories', $timezone);
        $this->update('default_relationship_types', $timezone);
        $this->update('default_relationship_type_groups', $timezone);
        $this->update('entries', $timezone);
        $this->update('events', $timezone);
        $this->update('genders', $timezone);
        $this->update('gifts', $timezone);
        $this->update('import_jobs', $timezone);
        $this->update('import_job_reports', $timezone);
        $this->update('instances', $timezone);
        $this->update('invitations', $timezone);
        DB::table('jobs')->chunkById(200, function ($models) use ($timezone) {
            foreach ($models as $model) {
                $created = is_null($model->created_at) ? null : Carbon::createFromTimeString($model->created_at, $timezone)->setTimezone('UTC');

                DB::table('jobs')->where('id', $model->id)
                    ->update([
                        'created_at' => $created,
                    ]);
            }
        });
        $this->update('journal_entries', $timezone);
        $this->update('metadata_love_relationships', $timezone);
        $this->update('notes', $timezone);
        $this->update('notifications', $timezone);
        $this->update('oauth_access_tokens', $timezone);
        $this->update('oauth_clients', $timezone);
        $this->update('oauth_personal_access_clients', $timezone);
        $this->update('pets', $timezone);
        $this->update('pet_categories', $timezone);
        $this->update('relationships', $timezone);
        $this->update('relationship_types', $timezone);
        $this->update('relationship_type_groups', $timezone);
        $this->update('reminders', $timezone);
        $this->update('reminder_rules', $timezone);
        $this->update('special_dates', $timezone);
        $this->update('statistics', $timezone);
        $this->update('subscriptions', $timezone);
        $this->update('tags', $timezone);
        $this->update('tasks', $timezone);
        $this->update('terms', $timezone);
        $this->update('term_user', $timezone, 'account_id');
        $this->update('u2f_key', $timezone);
        $this->update('users', $timezone);
    }

    /**
     * Update the timestamps table.
     *
     * @param  string  $table
     * @param  string  $timezone
     * @param  string  $id
     */
    private static function update($table, $timezone, $id = 'id')
    {
        DB::table($table)->orderBy($id)->chunk(200, function ($models) use ($table, $timezone, $id) {
            foreach ($models as $model) {
                $created = is_null($model->created_at) ? null : Carbon::createFromTimeString($model->created_at, $timezone)->setTimezone('UTC');
                $updated = is_null($model->updated_at) ? null : Carbon::createFromTimeString($model->updated_at, $timezone)->setTimezone('UTC');

                DB::table($table)->where($id, $model->$id)
                    ->update([
                        'created_at' => $created,
                        'updated_at' => $updated,
                    ]);
            }
        });
    }
}
