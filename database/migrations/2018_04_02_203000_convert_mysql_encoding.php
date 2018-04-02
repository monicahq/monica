<?php

use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Migrations\Migration;

class ConvertMysqlEncoding extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $connection = DB::connection();

        if ($connection instanceof MySqlConnection) {

            // varchar columns
            DB::statement('ALTER TABLE `accounts` CHANGE `api_key` `api_key` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `activities` CHANGE `summary` `summary` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `activity_type_groups` CHANGE `key` `key` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `activity_types` CHANGE `key` `key` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `activity_types` CHANGE `location_type` `location_type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `activity_types` CHANGE `icon` `icon` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `cache` CHANGE `key` `key` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `contacts` CHANGE `first_name` `first_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `contacts` CHANGE `middle_name` `middle_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `contacts` CHANGE `last_name` `last_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `contacts` CHANGE `surname` `surname` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `contacts` CHANGE `first_met_where` `first_met_where` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `contacts` CHANGE `job` `job` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `countries` CHANGE `iso` `iso` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `countries` CHANGE `country` `country` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `debts` CHANGE `in_debt` `in_debt` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `debts` CHANGE `status` `status` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `entries` CHANGE `title` `title` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `events` CHANGE `object_type` `object_type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `events` CHANGE `nature_of_operation` `nature_of_operation` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `gifts` CHANGE `name` `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `gifts` CHANGE `is_an_idea` `is_an_idea` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `gifts` CHANGE `has_been_offered` `has_been_offered` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `jobs` CHANGE `queue` `queue` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `migrations` CHANGE `migration` `migration` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `password_resets` CHANGE `email` `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `password_resets` CHANGE `token` `token` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `reminders` CHANGE `frequency_type` `frequency_type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `sessions` CHANGE `id` `id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `sessions` CHANGE `ip_address` `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `tasks` CHANGE `title` `title` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `users` CHANGE `first_name` `first_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `users` CHANGE `last_name` `last_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `users` CHANGE `email` `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `users` CHANGE `password` `password` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `users` CHANGE `remember_token` `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `users` CHANGE `timezone` `timezone` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `users` CHANGE `locale` `locale` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `users` CHANGE `metric` `metric` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `users` CHANGE `fluid_container` `fluid_container` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `users` CHANGE `contacts_sort_order` `contacts_sort_order` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');

            // text, tinytext, mediumtext, longtext columns
            DB::statement('ALTER TABLE `activities` CHANGE `description` `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `cache` CHANGE `value` `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `contacts` CHANGE `first_met_additional_info` `first_met_additional_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `contacts` CHANGE `food_preferencies` `food_preferencies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `debts` CHANGE `reason` `reason` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `entries` CHANGE `post` `post` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `failed_jobs` CHANGE `connection` `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `failed_jobs` CHANGE `queue` `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `failed_jobs` CHANGE `payload` `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `failed_jobs` CHANGE `exception` `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `gifts` CHANGE `comment` `comment` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `gifts` CHANGE `url` `url` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `jobs` CHANGE `payload` `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `notes` CHANGE `body` `body` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `reminders` CHANGE `title` `title` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `reminders` CHANGE `description` `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `sessions` CHANGE `user_agent` `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `sessions` CHANGE `payload` `payload` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `tasks` CHANGE `description` `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');

            // tables
            DB::statement('ALTER TABLE `accounts` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `activities` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `activity_contact` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `activity_statistics` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `activity_types` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `activity_type_groups` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `addresses` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `api_usage` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `cache` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `calls` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `contacts` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `contact_fields` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `contact_field_types` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `contact_tag` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `countries` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `debts` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `default_contact_field_types` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `default_relationship_types` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `default_relationship_type_groups` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `entries` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `events` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `failed_jobs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `genders` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `gifts` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `import_jobs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `import_job_reports` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `instances` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `invitations` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `jobs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `journal_entries` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `migrations` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `notes` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `notifications` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `oauth_access_tokens` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `oauth_auth_codes` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `oauth_clients` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `oauth_personal_access_clients` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `oauth_refresh_tokens` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `password_resets` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `pets` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `relationship_types` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `relationship_type_groups` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `reminders` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `reminders_sent` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `reminder_rules` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `sessions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `special_dates` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `statistics` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `subscriptions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `tags` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `tasks` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            DB::statement('ALTER TABLE `users` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');

            // DB
            $databasename = $connection->getDatabaseName();
            $pdo = $connection->getPdo();
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            DB::statement('ALTER DATABASE `'.$databasename.'` CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;');

            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        }
    }
}
