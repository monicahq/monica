
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accounts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `has_access_to_paid_version_for_free` tinyint(1) NOT NULL DEFAULT '0',
  `api_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `number_of_invitations_sent` int DEFAULT NULL,
  `default_time_reminder_is_sent` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '12:00',
  `default_gender_id` int unsigned DEFAULT NULL,
  `stripe_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `card_brand` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `card_last_four` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `legacy_free_plan_unlimited_contacts` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounts_default_gender_id_foreign` (`default_gender_id`),
  KEY `accounts_stripe_id_index` (`stripe_id`),
  CONSTRAINT `accounts_default_gender_id_foreign` FOREIGN KEY (`default_gender_id`) REFERENCES `genders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activities` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `activity_type_id` int unsigned DEFAULT NULL,
  `summary` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `happened_at` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activities_account_id_foreign` (`account_id`),
  KEY `activities_activity_type_id_foreign` (`activity_type_id`),
  CONSTRAINT `activities_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `activities_activity_type_id_foreign` FOREIGN KEY (`activity_type_id`) REFERENCES `activity_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `activities` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_contact` (
  `activity_id` int unsigned NOT NULL,
  `contact_id` int unsigned NOT NULL,
  `account_id` int unsigned NOT NULL,
  KEY `activity_contact_activity_id_foreign` (`activity_id`),
  KEY `activity_contact_contact_id_foreign` (`contact_id`),
  KEY `activity_contact_account_id_foreign` (`account_id`),
  CONSTRAINT `activity_contact_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `activity_contact_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `activity_contact_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `activity_contact` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_contact` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_statistics` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `contact_id` int unsigned NOT NULL,
  `year` int NOT NULL,
  `count` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_statistics_account_id_foreign` (`account_id`),
  KEY `activity_statistics_contact_id_foreign` (`contact_id`),
  CONSTRAINT `activity_statistics_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `activity_statistics_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `activity_statistics` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_statistics` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_type_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `translation_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_type_categories_account_id_foreign` (`account_id`),
  CONSTRAINT `activity_type_categories_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `activity_type_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_type_categories` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `activity_type_category_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `translation_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_types_account_id_foreign` (`account_id`),
  KEY `activity_types_activity_type_category_id_foreign` (`activity_type_category_id`),
  CONSTRAINT `activity_types_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `activity_types_activity_type_category_id_foreign` FOREIGN KEY (`activity_type_category_id`) REFERENCES `activity_type_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `activity_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_types` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `address_contact_field_label` (
  `contact_field_label_id` bigint unsigned NOT NULL,
  `address_id` int unsigned NOT NULL,
  `account_id` int unsigned NOT NULL,
  KEY `address_contact_field_label_index` (`contact_field_label_id`,`address_id`,`account_id`),
  KEY `address_contact_field_label_address_id_foreign` (`address_id`),
  KEY `address_contact_field_label_account_id_foreign` (`account_id`),
  CONSTRAINT `address_contact_field_label_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `address_contact_field_label_address_id_foreign` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `address_contact_field_label_contact_field_label_id_foreign` FOREIGN KEY (`contact_field_label_id`) REFERENCES `contact_field_labels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `address_contact_field_label` DISABLE KEYS */;
/*!40000 ALTER TABLE `address_contact_field_label` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `addresses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `place_id` int unsigned DEFAULT NULL,
  `contact_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `addresses_account_id_foreign` (`account_id`),
  KEY `addresses_contact_id_foreign` (`contact_id`),
  KEY `addresses_place_id_foreign` (`place_id`),
  CONSTRAINT `addresses_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `addresses_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `addresses_place_id_foreign` FOREIGN KEY (`place_id`) REFERENCES `places` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_usage` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_ip` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `api_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_usage` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `author_id` int unsigned DEFAULT NULL,
  `about_contact_id` int unsigned DEFAULT NULL,
  `author_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `objects` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `audited_at` datetime NOT NULL,
  `should_appear_on_dashboard` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_account_id_foreign` (`account_id`),
  KEY `audit_logs_author_id_foreign` (`author_id`),
  KEY `audit_logs_about_contact_id_foreign` (`about_contact_id`),
  CONSTRAINT `audit_logs_about_contact_id_foreign` FOREIGN KEY (`about_contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `audit_logs_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `audit_logs_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  UNIQUE KEY `cache_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calls` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `contact_id` int unsigned NOT NULL,
  `called_at` datetime NOT NULL,
  `content` mediumtext COLLATE utf8mb4_unicode_ci,
  `contact_called` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `calls_account_id_foreign` (`account_id`),
  KEY `calls_contact_id_foreign` (`contact_id`),
  CONSTRAINT `calls_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `calls_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `calls` DISABLE KEYS */;
/*!40000 ALTER TABLE `calls` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_of_employees` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `companies_account_id_foreign` (`account_id`),
  CONSTRAINT `companies_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_field_contact_field_label` (
  `contact_field_label_id` bigint unsigned NOT NULL,
  `contact_field_id` int unsigned NOT NULL,
  `account_id` int unsigned NOT NULL,
  KEY `contact_field_contact_field_label_index` (`contact_field_label_id`,`contact_field_id`,`account_id`),
  KEY `contact_field_contact_field_label_contact_field_id_foreign` (`contact_field_id`),
  KEY `contact_field_contact_field_label_account_id_foreign` (`account_id`),
  CONSTRAINT `contact_field_contact_field_label_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contact_field_contact_field_label_contact_field_id_foreign` FOREIGN KEY (`contact_field_id`) REFERENCES `contact_fields` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contact_field_contact_field_label_contact_field_label_id_foreign` FOREIGN KEY (`contact_field_label_id`) REFERENCES `contact_field_labels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `contact_field_contact_field_label` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_field_contact_field_label` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_field_labels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `label_i18n` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_field_labels_label_i18n_account_id_index` (`label_i18n`,`account_id`),
  KEY `contact_field_labels_label_account_id_index` (`label`,`account_id`),
  KEY `contact_field_labels_account_id_foreign` (`account_id`),
  CONSTRAINT `contact_field_labels_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `contact_field_labels` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_field_labels` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_field_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fontawesome_icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `protocol` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delible` tinyint(1) NOT NULL DEFAULT '1',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_field_types_account_id_foreign` (`account_id`),
  CONSTRAINT `contact_field_types_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `contact_field_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_field_types` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_fields` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `contact_id` int unsigned NOT NULL,
  `contact_field_type_id` int unsigned NOT NULL,
  `data` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_fields_account_id_foreign` (`account_id`),
  KEY `contact_fields_contact_id_foreign` (`contact_id`),
  KEY `contact_fields_contact_field_type_id_foreign` (`contact_field_type_id`),
  CONSTRAINT `contact_fields_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contact_fields_contact_field_type_id_foreign` FOREIGN KEY (`contact_field_type_id`) REFERENCES `contact_field_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contact_fields_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `contact_fields` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_fields` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_photo` (
  `contact_id` int unsigned NOT NULL,
  `photo_id` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  KEY `contact_photo_photo_id_foreign` (`photo_id`),
  KEY `contact_photo_contact_id_foreign` (`contact_id`),
  CONSTRAINT `contact_photo_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contact_photo_photo_id_foreign` FOREIGN KEY (`photo_id`) REFERENCES `photos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `contact_photo` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_photo` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_tag` (
  `contact_id` int unsigned NOT NULL,
  `tag_id` int unsigned NOT NULL,
  `account_id` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  KEY `contact_tag_account_id_foreign` (`account_id`),
  KEY `contact_tag_contact_id_foreign` (`contact_id`),
  KEY `contact_tag_tag_id_foreign` (`tag_id`),
  CONSTRAINT `contact_tag_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contact_tag_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contact_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `contact_tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_tag` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contacts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nickname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender_id` int DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_starred` tinyint(1) NOT NULL DEFAULT '0',
  `is_partial` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_dead` tinyint(1) NOT NULL DEFAULT '0',
  `deceased_special_date_id` int unsigned DEFAULT NULL,
  `deceased_reminder_id` int unsigned DEFAULT NULL,
  `last_talked_to` date DEFAULT NULL,
  `stay_in_touch_frequency` int DEFAULT NULL,
  `stay_in_touch_trigger_date` datetime DEFAULT NULL,
  `birthday_special_date_id` int unsigned DEFAULT NULL,
  `birthday_reminder_id` int unsigned DEFAULT NULL,
  `first_met_through_contact_id` int DEFAULT NULL,
  `first_met_special_date_id` int unsigned DEFAULT NULL,
  `first_met_reminder_id` int unsigned DEFAULT NULL,
  `first_met_where` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_met_additional_info` longtext COLLATE utf8mb4_unicode_ci,
  `job` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `food_preferences` longtext COLLATE utf8mb4_unicode_ci,
  `avatar_source` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `avatar_gravatar_url` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar_adorable_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar_adorable_url` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar_default_url` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar_photo_id` int unsigned DEFAULT NULL,
  `has_avatar` tinyint(1) NOT NULL DEFAULT '0',
  `avatar_external_url` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar_file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar_location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local',
  `gravatar_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_consulted_at` timestamp NULL DEFAULT NULL,
  `number_of_views` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `default_avatar_color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `has_avatar_bool` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `contacts_birthday_reminder_id_foreign` (`birthday_reminder_id`),
  KEY `contacts_first_met_reminder_id_foreign` (`first_met_reminder_id`),
  KEY `contacts_deceased_reminder_id_foreign` (`deceased_reminder_id`),
  KEY `contacts_birthday_special_date_id_foreign` (`birthday_special_date_id`),
  KEY `contacts_first_met_special_date_id_foreign` (`first_met_special_date_id`),
  KEY `contacts_deceased_special_date_id_foreign` (`deceased_special_date_id`),
  KEY `contacts_account_id_uuid_index` (`account_id`,`uuid`),
  KEY `contacts_avatar_photo_id_foreign` (`avatar_photo_id`),
  CONSTRAINT `contacts_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contacts_avatar_photo_id_foreign` FOREIGN KEY (`avatar_photo_id`) REFERENCES `photos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contacts_birthday_reminder_id_foreign` FOREIGN KEY (`birthday_reminder_id`) REFERENCES `reminders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contacts_birthday_special_date_id_foreign` FOREIGN KEY (`birthday_special_date_id`) REFERENCES `special_dates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contacts_deceased_reminder_id_foreign` FOREIGN KEY (`deceased_reminder_id`) REFERENCES `reminders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contacts_deceased_special_date_id_foreign` FOREIGN KEY (`deceased_special_date_id`) REFERENCES `special_dates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contacts_first_met_reminder_id_foreign` FOREIGN KEY (`first_met_reminder_id`) REFERENCES `reminders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contacts_first_met_special_date_id_foreign` FOREIGN KEY (`first_met_special_date_id`) REFERENCES `special_dates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `contacts` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `contact_id` int unsigned NOT NULL,
  `contact_field_type_id` int unsigned NOT NULL,
  `happened_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversations_account_id_foreign` (`account_id`),
  KEY `conversations_contact_id_foreign` (`contact_id`),
  KEY `conversations_contact_field_type_id_foreign` (`contact_field_type_id`),
  CONSTRAINT `conversations_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversations_contact_field_type_id_foreign` FOREIGN KEY (`contact_field_type_id`) REFERENCES `contact_field_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversations_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `conversations` DISABLE KEYS */;
/*!40000 ALTER TABLE `conversations` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crons` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `command` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_run` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crons_command_unique` (`command`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `crons` DISABLE KEYS */;
/*!40000 ALTER TABLE `crons` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `currencies` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `iso` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `symbol` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=154 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `currencies` DISABLE KEYS */;
INSERT INTO `currencies` VALUES (1,'CAD','Canadian Dollar','$',NULL,NULL),(2,'USD','US Dollar','$',NULL,NULL),(3,'GBP','British Pound','£',NULL,NULL),(4,'EUR','Euro','€',NULL,NULL),(5,'RUB','Russian Ruble','₽',NULL,NULL),(6,'ZAR','South African Rand','R ',NULL,NULL),(7,'DKK','Danish krone','kr.',NULL,NULL),(8,'INR','Indian rupee','₹',NULL,NULL),(9,'BRL','Brazilian Real','R$',NULL,NULL),(10,'CHF','Swiss CHF','CHF',NULL,NULL),(11,'AED','Emirati Dirham','.د.ب',NULL,NULL),(12,'AFN','Afghan Afghani','؋',NULL,NULL),(13,'ALL','Albanian lek','lek',NULL,NULL),(14,'AMD','Armenian dram','',NULL,NULL),(15,'ANG','Dutch Guilder','ƒ',NULL,NULL),(16,'AOA','Angolan Kwanza','Kz',NULL,NULL),(17,'ARS','Argentine peso','$',NULL,NULL),(18,'AUD','Australian Dollar','$',NULL,NULL),(19,'AWG','Arubin florin','ƒ',NULL,NULL),(20,'AZN','Azerbaijani manat','ман',NULL,NULL),(21,'BAM','Bosnian Convertible Marka','KM',NULL,NULL),(22,'BBD','Barbadian dollar','$',NULL,NULL),(23,'BDT','Bangladeshi Taka','Tk',NULL,NULL),(24,'BGN','Bulgarian lev','лв',NULL,NULL),(25,'BHD','Bahraini Dinar','.د.ب or BD',NULL,NULL),(26,'BIF','Burundian Franc','',NULL,NULL),(27,'BMD','Bermudian dollar','$',NULL,NULL),(28,'BND','Bruneian Dollar','$',NULL,NULL),(29,'BOB','Bolivian Boliviano','$b',NULL,NULL),(30,'BSD','Bahamian dollar','B$',NULL,NULL),(31,'BTN','Bhutanese Ngultrum','Nu.',NULL,NULL),(32,'BWP','Botswana Pula','P',NULL,NULL),(33,'BYR','Belarusian ruble','р',NULL,NULL),(34,'BZD','Belize dollar','BZ$',NULL,NULL),(35,'CLP','Chilean Peso','$',NULL,NULL),(36,'CNY','Yuan or chinese renminbi','¥',NULL,NULL),(37,'COP','Colombian peso','$',NULL,NULL),(38,'CRC','Costa Rican colón','₡',NULL,NULL),(39,'CUC','Cuban convertible peso','$',NULL,NULL),(40,'CUP','Cuban peso','₱',NULL,NULL),(41,'CVE','Cape Verdean Escudo','$',NULL,NULL),(42,'CZK','Czech koruna','Kč',NULL,NULL),(43,'DJF','Djiboutian Franc','fdj',NULL,NULL),(44,'DOP','Dominican peso','$',NULL,NULL),(45,'DZD','Algerian Dinar','جد',NULL,NULL),(46,'EGP','Egyptian Pound','£ ',NULL,NULL),(47,'ERN','Eritrean nakfa','ናቕፋ',NULL,NULL),(48,'ETB','Ethiopian Birr','Br',NULL,NULL),(49,'FJD','Fijian dollar','$',NULL,NULL),(50,'FKP','Falkland Island Pound','£',NULL,NULL),(51,'GEL','Georgian lari','ლ',NULL,NULL),(52,'GHS','Ghanaian Cedi','GH¢',NULL,NULL),(53,'GIP','Gibraltar pound','£',NULL,NULL),(54,'GMD','Gambian dalasi','',NULL,NULL),(55,'GNF','Guinean Franc','',NULL,NULL),(56,'GTQ','Guatemalan Quetzal','Q',NULL,NULL),(57,'GYD','Guyanese dollar','$',NULL,NULL),(58,'HKD','Hong Kong dollar','HK$',NULL,NULL),(59,'HNL','Honduran lempira','L',NULL,NULL),(60,'HRK','Croatian kuna','kn',NULL,NULL),(61,'HTG','Haitian gourde','G',NULL,NULL),(62,'HUF','Hungarian forint','Ft',NULL,NULL),(63,'IDR','Indonesian Rupiah','Rp',NULL,NULL),(64,'ILS','Israeli Shekel','₪',NULL,NULL),(65,'IQD','Iraqi Dinar','ع.د',NULL,NULL),(66,'IRR','Iranian Rial','',NULL,NULL),(67,'ISK','Icelandic Krona','kr',NULL,NULL),(68,'JMD','Jamaican dollar','J$',NULL,NULL),(69,'JOD','Jordanian Dinar','',NULL,NULL),(70,'JPY','Japanese yen','¥',NULL,NULL),(71,'KES','Kenyan Shilling','KSh',NULL,NULL),(72,'KGS','Kyrgyzstani som','лв',NULL,NULL),(73,'KHR','Cambodian Riel','៛',NULL,NULL),(74,'KMF','Comoran Franc','',NULL,NULL),(75,'KPW','North Korean won','₩',NULL,NULL),(76,'KRW','South Korean won','₩',NULL,NULL),(77,'KWD','Kuwaiti Dinar','ك',NULL,NULL),(78,'KYD','Caymanian Dollar','$',NULL,NULL),(79,'KZT','Kazakhstani tenge','₸',NULL,NULL),(80,'LAK','Lao or Laotian Kip','₭',NULL,NULL),(81,'LBP','Lebanese Pound','ل.ل',NULL,NULL),(82,'LKR','Sri Lankan Rupee','Rs',NULL,NULL),(83,'LRD','Liberian Dollar','$',NULL,NULL),(84,'LSL','Lesotho loti','L or M',NULL,NULL),(85,'LTL','Lithuanian litas','Lt',NULL,NULL),(86,'LYD','Libyan Dinar',' د.ل',NULL,NULL),(87,'MAD','Moroccan Dirham','م.د.',NULL,NULL),(88,'MDL','Moldovan Leu','L',NULL,NULL),(89,'MGA','Malagasy Ariary','Ar',NULL,NULL),(90,'MKD','Macedonian Denar','ден',NULL,NULL),(91,'MMK','Burmese Kyat','K',NULL,NULL),(92,'MNT','Mongolian Tughrik','₮',NULL,NULL),(93,'MOP','Macau Pataca','MOP$',NULL,NULL),(94,'MRO','Mauritanian Ouguiya','UM',NULL,NULL),(95,'MUR','Mauritian rupee','Rs',NULL,NULL),(96,'MVR','Maldivian Rufiyaa','rf',NULL,NULL),(97,'MWK','Malawian Kwacha','MK',NULL,NULL),(98,'MXN','Mexico Peso','$',NULL,NULL),(99,'MYR','Malaysian Ringgit','RM',NULL,NULL),(100,'MZN','Mozambican Metical','MT',NULL,NULL),(101,'NAD','Namibian Dollar','$',NULL,NULL),(102,'NGN','Nigerian Naira','₦',NULL,NULL),(103,'NIO','Nicaraguan córdoba','C$',NULL,NULL),(104,'NOK','Norwegian krone','kr',NULL,NULL),(105,'NPR','Nepalese Rupee','Rs',NULL,NULL),(106,'NZD','New Zealand Dollar','$',NULL,NULL),(107,'OMR','Omani Rial','ع.ر.',NULL,NULL),(108,'PAB','Balboa panamérn','B/',NULL,NULL),(109,'PEN','Peruvian nuevo sol','S/',NULL,NULL),(110,'PGK','Papua New Guinean Kina','K',NULL,NULL),(111,'PHP','Philippine Peso','₱',NULL,NULL),(112,'PKR','Pakistani Rupee','Rs',NULL,NULL),(113,'PLN','Polish złoty','zł',NULL,NULL),(114,'PYG','Paraguayan guarani','₲',NULL,NULL),(115,'QAR','Qatari Riyal','ق.ر ',NULL,NULL),(116,'RON','Romanian leu','lei',NULL,NULL),(117,'RSD','Serbian Dinar','РСД',NULL,NULL),(118,'RWF','Rwandan franc','FRw, RF, R₣',NULL,NULL),(119,'SAR','Saudi Arabian Riyal','ر.س',NULL,NULL),(120,'SBD','Solomon Islander Dollar','SI$',NULL,NULL),(121,'SCR','Seychellois Rupee','Rs',NULL,NULL),(122,'SDG','Sudanese Pound','',NULL,NULL),(123,'SEK','Swedish krona','kr',NULL,NULL),(124,'SGD','Singapore Dollar','$',NULL,NULL),(125,'SLL','Sierra Leonean Leone','Le',NULL,NULL),(126,'SOS','Somali Shilling','S',NULL,NULL),(127,'SRD','Surinamese dollar','$',NULL,NULL),(128,'SSP','South Sudanese pound','£',NULL,NULL),(129,'SYP','Syrian Pound','£',NULL,NULL),(130,'SZL','Swazi Lilangeni','L or E',NULL,NULL),(131,'THB','Thai Baht','฿',NULL,NULL),(132,'TJS','Tajikistani somoni','',NULL,NULL),(133,'TMT','Turkmenistan manat','T',NULL,NULL),(134,'TND','Tunisian Dinar','',NULL,NULL),(135,'TOP','Tongan Pa\'anga','T$',NULL,NULL),(136,'TRY','Turkish Lira','',NULL,NULL),(137,'TTD','Trinidadian dollar','TT$',NULL,NULL),(138,'TWD','Taiwan New Dollar','NT$',NULL,NULL),(139,'TZS','Tanzanian Shilling','Sh',NULL,NULL),(140,'UAH','Ukrainian Hryvnia','₴',NULL,NULL),(141,'UGX','Ugandan Shilling','USh',NULL,NULL),(142,'UYU','Uruguayan peso','$U',NULL,NULL),(143,'UZS','Uzbekistani som','лв',NULL,NULL),(144,'VEF','Venezuelan bolivar','Bs',NULL,NULL),(145,'VND','Vietnamese Dong','₫',NULL,NULL),(146,'VUV','Ni-Vanuatu Vatu','VT',NULL,NULL),(147,'WST','Samoan Tālā','$',NULL,NULL),(148,'XCD','East Caribbean dollar','EC$',NULL,NULL),(149,'XOF','CFA Franc','',NULL,NULL),(150,'XPF','CFP Franc','',NULL,NULL),(151,'YER','Yemeni Rial','',NULL,NULL),(152,'ZMW','Zambian Kwacha','ZMK',NULL,NULL),(153,'ZWD','Zimbabwean Dollar','Z$',NULL,NULL);
/*!40000 ALTER TABLE `currencies` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `days` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `date` date NOT NULL,
  `rate` int NOT NULL,
  `comment` mediumtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `days_account_id_foreign` (`account_id`),
  CONSTRAINT `days_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `days` DISABLE KEYS */;
/*!40000 ALTER TABLE `days` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `debts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `contact_id` int unsigned NOT NULL,
  `in_debt` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'inprogress',
  `amount` int NOT NULL,
  `currency_id` int unsigned DEFAULT NULL,
  `reason` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `debts_account_id_foreign` (`account_id`),
  KEY `debts_contact_id_foreign` (`contact_id`),
  KEY `debts_currency_id_foreign` (`currency_id`),
  CONSTRAINT `debts_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `debts_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `debts_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `debts` DISABLE KEYS */;
/*!40000 ALTER TABLE `debts` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `default_activity_type_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `translation_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `default_activity_type_categories` DISABLE KEYS */;
INSERT INTO `default_activity_type_categories` VALUES (1,'simple_activities','2020-05-21 19:15:16','2020-05-21 19:15:16'),(2,'sport','2020-05-21 19:15:16','2020-05-21 19:15:16'),(3,'food','2020-05-21 19:15:16','2020-05-21 19:15:16'),(4,'cultural_activities','2020-05-21 19:15:16','2020-05-21 19:15:16');
/*!40000 ALTER TABLE `default_activity_type_categories` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `default_activity_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `default_activity_type_category_id` int NOT NULL,
  `translation_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `default_activity_types` DISABLE KEYS */;
INSERT INTO `default_activity_types` VALUES (1,1,'just_hung_out','outside','2020-05-21 19:15:16','2020-05-21 19:15:16'),(2,1,'watched_movie_at_home','my_place','2020-05-21 19:15:16','2020-05-21 19:15:16'),(3,1,'talked_at_home','my_place','2020-05-21 19:15:16','2020-05-21 19:15:16'),(4,2,'did_sport_activities_together','outside','2020-05-21 19:15:16','2020-05-21 19:15:16'),(5,3,'ate_at_his_place','his_place','2020-05-21 19:15:16','2020-05-21 19:15:16'),(6,3,'went_bar','outside','2020-05-21 19:15:16','2020-05-21 19:15:16'),(7,3,'ate_at_home','my_place','2020-05-21 19:15:16','2020-05-21 19:15:16'),(8,3,'picnicked','outside','2020-05-21 19:15:16','2020-05-21 19:15:16'),(9,3,'ate_restaurant','outside','2020-05-21 19:15:16','2020-05-21 19:15:16'),(10,4,'went_theater','outside','2020-05-21 19:15:16','2020-05-21 19:15:16'),(11,4,'went_concert','outside','2020-05-21 19:15:16','2020-05-21 19:15:16'),(12,4,'went_play','outside','2020-05-21 19:15:16','2020-05-21 19:15:16'),(13,4,'went_museum','outside','2020-05-21 19:15:16','2020-05-21 19:15:16');
/*!40000 ALTER TABLE `default_activity_types` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `default_contact_field_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fontawesome_icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `protocol` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `migrated` tinyint(1) NOT NULL DEFAULT '0',
  `delible` tinyint(1) NOT NULL DEFAULT '1',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `default_contact_field_types` DISABLE KEYS */;
INSERT INTO `default_contact_field_types` VALUES (1,'Email','fa fa-envelope-open-o','mailto:',1,0,'email',NULL,NULL),(2,'Phone','fa fa-volume-control-phone','tel:',1,0,'phone',NULL,NULL),(3,'Facebook','fa fa-facebook-official','https://facebook.com/',1,1,NULL,NULL,NULL),(4,'Twitter','fa fa-twitter-square',NULL,1,1,NULL,NULL,NULL),(5,'Whatsapp','fa fa-whatsapp','https://wa.me/',1,1,NULL,NULL,NULL),(6,'Telegram','fa fa-telegram','telegram:',1,1,NULL,NULL,NULL),(7,'LinkedIn','fa fa-linkedin-square',NULL,1,1,NULL,NULL,NULL);
/*!40000 ALTER TABLE `default_contact_field_types` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `default_contact_modules` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `translation_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `delible` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `migrated` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `default_contact_modules` DISABLE KEYS */;
INSERT INTO `default_contact_modules` VALUES (1,'love_relationships','app.relationship_type_group_love',0,1,1,NULL,NULL),(2,'family_relationships','app.relationship_type_group_family',0,1,1,NULL,NULL),(3,'other_relationships','app.relationship_type_group_other',0,1,1,NULL,NULL),(4,'pets','people.pets_title',0,1,1,NULL,NULL),(5,'contact_information','people.section_contact_information',0,1,1,NULL,NULL),(6,'addresses','people.contact_address_title',0,1,1,NULL,NULL),(7,'how_you_met','people.introductions_sidebar_title',0,1,1,NULL,NULL),(8,'work_information','people.work_information',0,1,1,NULL,NULL),(9,'food_preferences','people.food_preferences_title',0,1,1,NULL,NULL),(10,'notes','people.section_personal_notes',0,1,1,NULL,NULL),(11,'phone_calls','people.call_title',0,1,1,NULL,NULL),(12,'activities','people.activity_title',0,1,1,NULL,NULL),(13,'reminders','people.section_personal_reminders',0,1,1,NULL,NULL),(14,'tasks','people.section_personal_tasks',0,1,1,NULL,NULL),(15,'gifts','people.gifts_title',0,1,1,NULL,NULL),(16,'debts','people.debt_title',0,1,1,NULL,NULL),(17,'conversations','people.conversation_list_title',0,1,1,NULL,NULL),(18,'documents','people.document_list_title',0,1,1,NULL,NULL);
/*!40000 ALTER TABLE `default_contact_modules` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `default_life_event_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `translation_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `migrated` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `default_life_event_categories` DISABLE KEYS */;
INSERT INTO `default_life_event_categories` VALUES (1,'work_education',0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(2,'family_relationships',0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(3,'home_living',0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(4,'health_wellness',0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(5,'travel_experiences',0,'2020-05-21 19:15:22','2020-05-21 19:15:22');
/*!40000 ALTER TABLE `default_life_event_categories` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `default_life_event_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `default_life_event_category_id` int unsigned NOT NULL,
  `translation_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `specific_information_structure` text COLLATE utf8mb4_unicode_ci,
  `migrated` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `default_life_event_types_default_life_event_category_id_foreign` (`default_life_event_category_id`),
  CONSTRAINT `default_life_event_types_default_life_event_category_id_foreign` FOREIGN KEY (`default_life_event_category_id`) REFERENCES `default_life_event_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `default_life_event_types` DISABLE KEYS */;
INSERT INTO `default_life_event_types` VALUES (1,1,'new_job','{\"employer\": {\"type\": \"string\", \"value\": \"\"}, \"job_title\": {\"type\": \"string\", \"value\": \"\"}}',0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(2,1,'retirement','{\"profession\": {\"type\": \"string\", \"value\": \"\"}}',0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(3,1,'new_school','{\"degree\": {\"type\": \"string\", \"value\": \"\"}, \"end_date\": {\"type\": \"date\", \"value\": \"\"}, \"end_date_reminder_id\": {\"type\": \"integer\", \"value\": \"\"}, \"school_name\": {\"type\": \"string\", \"value\": \"\"}, \"studying\": {\"type\": \"string\", \"value\": \"\"}}',0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(4,1,'study_abroad','{\"degree\": {\"type\": \"string\", \"value\": \"\"}, \"end_date\": {\"type\": \"date\", \"value\": \"\"}, \"end_date_reminder_id\": {\"type\": \"integer\", \"value\": \"\"}, \"school_name\": {\"type\": \"string\", \"value\": \"\"}, \"studying\": {\"type\": \"string\", \"value\": \"\"}}',0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(5,1,'volunteer_work','{\"organization\": {\"type\": \"string\", \"value\": \"\"}}',0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(6,1,'published_book_or_paper','{\"full_citation\": {\"type\": \"string\", \"value\": \"\"}, \"url\": {\"type\": \"string\", \"value\": \"\"}, \"citation\": {\"type\": \"string\", \"value\": \"\"}}',0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(7,1,'military_service','{\"end_date\": {\"type\": \"date\", \"value\": \"\"}, \"end_date_reminder_id\": {\"type\": \"integer\", \"value\": \"\"}, \"branch\": {\"type\": \"string\", \"value\": \"\"}, \"division\": {\"type\": \"string\", \"value\": \"\"}, \"country\": {\"type\": \"string\", \"value\": \"\"}}',0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(8,2,'new_relationship',NULL,0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(9,2,'engagement','{\"with_contact_id\": {\"type\": \"integer\", \"value\": \"\"}}',0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(10,2,'marriage','{\"with_contact_id\": {\"type\": \"integer\", \"value\": \"\"}}',0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(11,2,'anniversary',NULL,0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(12,2,'expecting_a_baby','{\"contact_id\": {\"type\": \"integer\", \"value\": \"\"}, \"expected_date\": {\"type\": \"date\", \"value\": \"\"}, \"expected_date_reminder_id\": {\"type\": \"integer\", \"value\": \"\"}, \"expected_gender\": {\"type\": \"string\", \"value\": \"\"}}',0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(13,2,'new_child',NULL,0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(14,2,'new_family_member',NULL,0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(15,2,'new_pet',NULL,0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(16,2,'end_of_relationship','{\"breakup_reason\": {\"type\": \"string\", \"value\": \"\"}, \"who_broke_up_contact_id\": {\"type\": \"integer\", \"value\": \"\"}}',0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(17,2,'loss_of_a_loved_one',NULL,0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(18,3,'moved','{\"where_to\": {\"type\": \"string\", \"value\": \"\"}}',0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(19,3,'bought_a_home','{\"address\": {\"type\": \"string\", \"value\": \"\"}, \"estimated_value\": {\"type\": \"number\", \"value\": \"\"}}',0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(20,3,'home_improvement',NULL,0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(21,3,'holidays','{\"where\": {\"type\": \"string\", \"value\": \"\"}, \"duration_in_days\": {\"type\": \"integer\", \"value\": \"\"}}',0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(22,3,'new_vehicle','{\"type\": {\"type\": \"string\", \"value\": \"\"}, \"model\": {\"type\": \"string\", \"value\": \"\"}, \"model_year\": {\"type\": \"string\", \"value\": \"\"}}',0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(23,3,'new_roommate','{\"contact_id\": {\"type\": \"string\", \"value\": \"\"}}',0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(24,4,'overcame_an_illness',NULL,0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(25,4,'quit_a_habit',NULL,0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(26,4,'new_eating_habits',NULL,0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(27,4,'weight_loss','{\"amount\": {\"type\": \"string\", \"value\": \"\"}, \"unit\": {\"type\": \"string\", \"value\": \"\"}}',0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(28,4,'wear_glass_or_contact',NULL,0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(29,4,'broken_bone',NULL,0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(30,4,'removed_braces',NULL,0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(31,4,'surgery','{\"nature\": {\"type\": \"string\", \"value\": \"\"}, \"number_days_in_hospital\": {\"type\": \"integer\", \"value\": \"\"}, \"number_days_in_hospital\": {\"type\": \"integer\", \"value\": \"\"}, \"expected_date_out_of_hospital_reminder_id\": {\"type\": \"integer\", \"value\": \"\"}}',0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(32,4,'dentist',NULL,0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(33,5,'new_sport',NULL,0,'2020-05-21 19:15:22','2020-05-21 19:15:22'),(34,5,'new_hobby',NULL,0,'2020-05-21 19:15:23','2020-05-21 19:15:23'),(35,5,'new_instrument',NULL,0,'2020-05-21 19:15:23','2020-05-21 19:15:23'),(36,5,'new_language',NULL,0,'2020-05-21 19:15:23','2020-05-21 19:15:23'),(37,5,'tattoo_or_piercing',NULL,0,'2020-05-21 19:15:23','2020-05-21 19:15:23'),(38,5,'new_license',NULL,0,'2020-05-21 19:15:23','2020-05-21 19:15:23'),(39,5,'travel','{\"visited_place\": {\"type\": \"string\", \"value\": \"\"}, \"duration_in_days\": {\"type\": \"integer\", \"value\": \"\"}}',0,'2020-05-21 19:15:23','2020-05-21 19:15:23'),(40,5,'achievement_or_award',NULL,0,'2020-05-21 19:15:23','2020-05-21 19:15:23'),(41,5,'changed_beliefs',NULL,0,'2020-05-21 19:15:23','2020-05-21 19:15:23'),(42,5,'first_word',NULL,0,'2020-05-21 19:15:23','2020-05-21 19:15:23'),(43,5,'first_kiss',NULL,0,'2020-05-21 19:15:23','2020-05-21 19:15:23');
/*!40000 ALTER TABLE `default_life_event_types` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `default_relationship_type_groups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `delible` tinyint(1) NOT NULL DEFAULT '0',
  `migrated` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `default_relationship_type_groups` DISABLE KEYS */;
INSERT INTO `default_relationship_type_groups` VALUES (1,'love',0,1,NULL,NULL),(2,'family',0,1,NULL,NULL),(3,'friend',0,1,NULL,NULL),(4,'work',0,1,NULL,NULL);
/*!40000 ALTER TABLE `default_relationship_type_groups` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `default_relationship_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_reverse_relationship` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `relationship_type_group_id` int NOT NULL,
  `delible` tinyint(1) NOT NULL DEFAULT '0',
  `migrated` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `default_relationship_types_migrated_index` (`migrated`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `default_relationship_types` DISABLE KEYS */;
INSERT INTO `default_relationship_types` VALUES (1,'partner','partner',1,0,1,NULL,NULL),(2,'spouse','spouse',1,0,1,NULL,NULL),(3,'date','date',1,0,1,NULL,NULL),(4,'lover','lover',1,0,1,NULL,NULL),(5,'inlovewith','lovedby',1,0,1,NULL,NULL),(6,'lovedby','inlovewith',1,0,1,NULL,NULL),(7,'ex','ex',1,0,1,NULL,NULL),(8,'parent','child',2,0,1,NULL,NULL),(9,'child','parent',2,0,1,NULL,NULL),(10,'sibling','sibling',2,0,1,NULL,NULL),(11,'grandparent','grandchild',2,0,1,NULL,NULL),(12,'grandchild','grandparent',2,0,1,NULL,NULL),(13,'uncle','nephew',2,0,1,NULL,NULL),(14,'nephew','uncle',2,0,1,NULL,NULL),(15,'cousin','cousin',2,0,1,NULL,NULL),(16,'godfather','godson',2,0,1,NULL,NULL),(17,'godson','godfather',2,0,1,NULL,NULL),(18,'friend','friend',3,0,1,NULL,NULL),(19,'bestfriend','bestfriend',3,0,1,NULL,NULL),(20,'colleague','colleague',4,0,1,NULL,NULL),(21,'boss','subordinate',4,0,1,NULL,NULL),(22,'subordinate','boss',4,0,1,NULL,NULL),(23,'mentor','protege',4,0,1,NULL,NULL),(24,'protege','mentor',4,0,1,NULL,NULL),(25,'ex_husband','ex_husband',1,0,1,NULL,NULL),(26,'stepparent','stepchild',2,0,1,NULL,NULL),(27,'stepchild','stepparent',2,0,1,NULL,NULL);
/*!40000 ALTER TABLE `default_relationship_types` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documents` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `contact_id` int unsigned NOT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `new_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filesize` int DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_of_downloads` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documents_account_id_foreign` (`account_id`),
  KEY `documents_contact_id_foreign` (`contact_id`),
  CONSTRAINT `documents_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documents_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emotion_activity` (
  `account_id` int unsigned NOT NULL,
  `activity_id` int unsigned NOT NULL,
  `emotion_id` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  KEY `emotion_activity_account_id_foreign` (`account_id`),
  KEY `emotion_activity_activity_id_foreign` (`activity_id`),
  KEY `emotion_activity_emotion_id_foreign` (`emotion_id`),
  CONSTRAINT `emotion_activity_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emotion_activity_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emotion_activity_emotion_id_foreign` FOREIGN KEY (`emotion_id`) REFERENCES `emotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `emotion_activity` DISABLE KEYS */;
/*!40000 ALTER TABLE `emotion_activity` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emotion_call` (
  `account_id` int unsigned NOT NULL,
  `call_id` int unsigned NOT NULL,
  `emotion_id` int unsigned NOT NULL,
  `contact_id` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  KEY `emotion_call_account_id_foreign` (`account_id`),
  KEY `emotion_call_call_id_foreign` (`call_id`),
  KEY `emotion_call_emotion_id_foreign` (`emotion_id`),
  KEY `emotion_call_contact_id_foreign` (`contact_id`),
  CONSTRAINT `emotion_call_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emotion_call_call_id_foreign` FOREIGN KEY (`call_id`) REFERENCES `calls` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emotion_call_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emotion_call_emotion_id_foreign` FOREIGN KEY (`emotion_id`) REFERENCES `emotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `emotion_call` DISABLE KEYS */;
/*!40000 ALTER TABLE `emotion_call` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emotions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `emotion_primary_id` int unsigned NOT NULL,
  `emotion_secondary_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `emotions_emotion_primary_id_foreign` (`emotion_primary_id`),
  KEY `emotions_emotion_secondary_id_foreign` (`emotion_secondary_id`),
  CONSTRAINT `emotions_emotion_primary_id_foreign` FOREIGN KEY (`emotion_primary_id`) REFERENCES `emotions_primary` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emotions_emotion_secondary_id_foreign` FOREIGN KEY (`emotion_secondary_id`) REFERENCES `emotions_secondary` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=134 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `emotions` DISABLE KEYS */;
INSERT INTO `emotions` VALUES (1,1,1,'adoration',NULL,NULL),(2,1,1,'affection',NULL,NULL),(3,1,1,'love',NULL,NULL),(4,1,1,'fondness',NULL,NULL),(5,1,1,'liking',NULL,NULL),(6,1,1,'attraction',NULL,NULL),(7,1,1,'caring',NULL,NULL),(8,1,1,'tenderness',NULL,NULL),(9,1,1,'compassion',NULL,NULL),(10,1,1,'sentimentality',NULL,NULL),(11,1,2,'arousal',NULL,NULL),(12,1,2,'desire',NULL,NULL),(13,1,2,'lust',NULL,NULL),(14,1,2,'passion',NULL,NULL),(15,1,2,'infatuation',NULL,NULL),(16,1,3,'longing',NULL,NULL),(17,2,4,'amusement',NULL,NULL),(18,2,4,'bliss',NULL,NULL),(19,2,4,'cheerfulness',NULL,NULL),(20,2,4,'gaiety',NULL,NULL),(21,2,4,'glee',NULL,NULL),(22,2,4,'jolliness',NULL,NULL),(23,2,4,'joviality',NULL,NULL),(24,2,4,'joy',NULL,NULL),(25,2,4,'delight',NULL,NULL),(26,2,4,'enjoyment',NULL,NULL),(27,2,4,'gladness',NULL,NULL),(28,2,4,'happiness',NULL,NULL),(29,2,4,'jubilation',NULL,NULL),(30,2,4,'elation',NULL,NULL),(31,2,4,'satisfaction',NULL,NULL),(32,2,4,'ecstasy',NULL,NULL),(33,2,4,'euphoria',NULL,NULL),(34,2,5,'enthusiasm',NULL,NULL),(35,2,5,'zeal',NULL,NULL),(36,2,5,'zest',NULL,NULL),(37,2,5,'excitement',NULL,NULL),(38,2,5,'thrill',NULL,NULL),(39,2,5,'exhilaration',NULL,NULL),(40,2,6,'contentment',NULL,NULL),(41,2,6,'pleasure',NULL,NULL),(42,2,7,'pride',NULL,NULL),(43,2,7,'pleasure',NULL,NULL),(44,2,8,'eagerness',NULL,NULL),(45,2,8,'hope',NULL,NULL),(46,2,9,'enthrallment',NULL,NULL),(47,2,9,'rapture',NULL,NULL),(48,2,10,'relief',NULL,NULL),(49,3,11,'amazement',NULL,NULL),(50,3,11,'surprise',NULL,NULL),(51,3,11,'astonishment',NULL,NULL),(52,4,12,'aggravation',NULL,NULL),(53,4,12,'irritation',NULL,NULL),(54,4,12,'agitation',NULL,NULL),(55,4,12,'annoyance',NULL,NULL),(56,4,12,'grouchiness',NULL,NULL),(57,4,12,'grumpiness',NULL,NULL),(58,4,13,'exasperation',NULL,NULL),(59,4,13,'frustration',NULL,NULL),(60,4,14,'anger',NULL,NULL),(61,4,14,'rage',NULL,NULL),(62,4,14,'outrage',NULL,NULL),(63,4,14,'fury',NULL,NULL),(64,4,14,'wrath',NULL,NULL),(65,4,14,'hostility',NULL,NULL),(66,4,14,'ferocity',NULL,NULL),(67,4,14,'bitterness',NULL,NULL),(68,4,14,'hate',NULL,NULL),(69,4,14,'loathing',NULL,NULL),(70,4,14,'scorn',NULL,NULL),(71,4,14,'spite',NULL,NULL),(72,4,14,'vengefulness',NULL,NULL),(73,4,14,'dislike',NULL,NULL),(74,4,14,'resentment',NULL,NULL),(75,4,15,'disgust',NULL,NULL),(76,4,15,'revulsion',NULL,NULL),(77,4,15,'contempt',NULL,NULL),(78,4,16,'envy',NULL,NULL),(79,4,16,'jealousy',NULL,NULL),(80,5,17,'agony',NULL,NULL),(81,5,17,'suffering',NULL,NULL),(82,5,17,'hurt',NULL,NULL),(83,5,17,'anguish',NULL,NULL),(84,5,18,'depression',NULL,NULL),(85,5,18,'despair',NULL,NULL),(86,5,18,'hopelessness',NULL,NULL),(87,5,18,'gloom',NULL,NULL),(88,5,18,'glumness',NULL,NULL),(89,5,18,'sadness',NULL,NULL),(90,5,18,'unhappiness',NULL,NULL),(91,5,18,'grief',NULL,NULL),(92,5,18,'sorrow',NULL,NULL),(93,5,18,'woe',NULL,NULL),(94,5,18,'misery',NULL,NULL),(95,5,18,'melancholy',NULL,NULL),(96,5,19,'dismay',NULL,NULL),(97,5,19,'disappointment',NULL,NULL),(98,5,19,'displeasure',NULL,NULL),(99,5,20,'guilt',NULL,NULL),(100,5,20,'shame',NULL,NULL),(101,5,20,'regret',NULL,NULL),(102,5,20,'remorse',NULL,NULL),(103,5,21,'alienation',NULL,NULL),(104,5,21,'isolation',NULL,NULL),(105,5,21,'neglect',NULL,NULL),(106,5,21,'loneliness',NULL,NULL),(107,5,21,'rejection',NULL,NULL),(108,5,21,'homesickness',NULL,NULL),(109,5,21,'defeat',NULL,NULL),(110,5,21,'dejection',NULL,NULL),(111,5,21,'insecurity',NULL,NULL),(112,5,21,'embarrassment',NULL,NULL),(113,5,21,'humiliation',NULL,NULL),(114,5,21,'insult',NULL,NULL),(115,5,22,'pity',NULL,NULL),(116,5,22,'sympathy',NULL,NULL),(117,6,23,'alarm',NULL,NULL),(118,6,23,'shock',NULL,NULL),(119,6,23,'fear',NULL,NULL),(120,6,23,'fright',NULL,NULL),(121,6,23,'horror',NULL,NULL),(122,6,23,'terror',NULL,NULL),(123,6,23,'panic',NULL,NULL),(124,6,23,'hysteria',NULL,NULL),(125,6,23,'mortification',NULL,NULL),(126,6,24,'anxiety',NULL,NULL),(127,6,24,'nervousness',NULL,NULL),(128,6,24,'tenseness',NULL,NULL),(129,6,24,'uneasiness',NULL,NULL),(130,6,24,'apprehension',NULL,NULL),(131,6,24,'worry',NULL,NULL),(132,6,24,'distress',NULL,NULL),(133,6,24,'dread',NULL,NULL);
/*!40000 ALTER TABLE `emotions` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emotions_primary` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `emotions_primary` DISABLE KEYS */;
INSERT INTO `emotions_primary` VALUES (1,'love',NULL,NULL),(2,'joy',NULL,NULL),(3,'surprise',NULL,NULL),(4,'anger',NULL,NULL),(5,'sadness',NULL,NULL),(6,'fear',NULL,NULL);
/*!40000 ALTER TABLE `emotions_primary` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emotions_secondary` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `emotion_primary_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `emotions_secondary_emotion_primary_id_foreign` (`emotion_primary_id`),
  CONSTRAINT `emotions_secondary_emotion_primary_id_foreign` FOREIGN KEY (`emotion_primary_id`) REFERENCES `emotions_primary` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `emotions_secondary` DISABLE KEYS */;
INSERT INTO `emotions_secondary` VALUES (1,1,'affection',NULL,NULL),(2,1,'lust',NULL,NULL),(3,1,'longing',NULL,NULL),(4,2,'cheerfulness',NULL,NULL),(5,2,'zest',NULL,NULL),(6,2,'contentment',NULL,NULL),(7,2,'pride',NULL,NULL),(8,2,'optimism',NULL,NULL),(9,2,'enthrallment',NULL,NULL),(10,2,'relief',NULL,NULL),(11,3,'surprise',NULL,NULL),(12,4,'irritation',NULL,NULL),(13,4,'exasperation',NULL,NULL),(14,4,'rage',NULL,NULL),(15,4,'disgust',NULL,NULL),(16,4,'envy',NULL,NULL),(17,5,'suffering',NULL,NULL),(18,5,'sadness',NULL,NULL),(19,5,'disappointment',NULL,NULL),(20,5,'shame',NULL,NULL),(21,5,'neglect',NULL,NULL),(22,5,'sympathy',NULL,NULL),(23,6,'horror',NULL,NULL),(24,6,'nervousness',NULL,NULL);
/*!40000 ALTER TABLE `emotions_secondary` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entries` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `post` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entries_account_id_foreign` (`account_id`),
  CONSTRAINT `entries_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `entries` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `genders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `genders_account_id_foreign` (`account_id`),
  CONSTRAINT `genders_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `genders` DISABLE KEYS */;
/*!40000 ALTER TABLE `genders` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gift_photo` (
  `photo_id` int unsigned NOT NULL,
  `gift_id` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`photo_id`,`gift_id`),
  KEY `gift_photo_gift_id_foreign` (`gift_id`),
  CONSTRAINT `gift_photo_gift_id_foreign` FOREIGN KEY (`gift_id`) REFERENCES `gifts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gift_photo_photo_id_foreign` FOREIGN KEY (`photo_id`) REFERENCES `photos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `gift_photo` DISABLE KEYS */;
/*!40000 ALTER TABLE `gift_photo` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gifts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `contact_id` int unsigned NOT NULL,
  `is_for` int unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` longtext COLLATE utf8mb4_unicode_ci,
  `url` longtext COLLATE utf8mb4_unicode_ci,
  `amount` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency_id` int unsigned DEFAULT NULL,
  `status` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'idea',
  `date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gifts_account_id_foreign` (`account_id`),
  KEY `gifts_contact_id_foreign` (`contact_id`),
  KEY `gifts_is_for_foreign` (`is_for`),
  KEY `gifts_currency_id_foreign` (`currency_id`),
  CONSTRAINT `gifts_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gifts_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gifts_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `gifts_is_for_foreign` FOREIGN KEY (`is_for`) REFERENCES `contacts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `gifts` DISABLE KEYS */;
/*!40000 ALTER TABLE `gifts` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `import_job_reports` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `import_job_id` int unsigned NOT NULL,
  `contact_information` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `skipped` tinyint(1) NOT NULL,
  `skip_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `import_job_reports_account_id_foreign` (`account_id`),
  KEY `import_job_reports_user_id_foreign` (`user_id`),
  KEY `import_job_reports_import_job_id_foreign` (`import_job_id`),
  CONSTRAINT `import_job_reports_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `import_job_reports_import_job_id_foreign` FOREIGN KEY (`import_job_id`) REFERENCES `import_jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `import_job_reports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `import_job_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `import_job_reports` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `import_jobs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'vcard',
  `contacts_found` int DEFAULT NULL,
  `contacts_skipped` int DEFAULT NULL,
  `contacts_imported` int DEFAULT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `started_at` date DEFAULT NULL,
  `ended_at` date DEFAULT NULL,
  `failed` tinyint(1) NOT NULL DEFAULT '0',
  `failed_reason` mediumtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `import_jobs_account_id_foreign` (`account_id`),
  KEY `import_jobs_user_id_foreign` (`user_id`),
  CONSTRAINT `import_jobs_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `import_jobs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `import_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `import_jobs` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `instances` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `current_version` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `latest_version` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latest_release_notes` mediumtext COLLATE utf8mb4_unicode_ci,
  `number_of_versions_since_current_version` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `instances` DISABLE KEYS */;
INSERT INTO `instances` VALUES (1,'5ec6ef58a9e01','2.17.0','2.17.0',NULL,NULL,'2020-05-21 19:15:04','2020-05-21 19:15:04');
/*!40000 ALTER TABLE `instances` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invitations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `invited_by_user_id` int unsigned NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invitation_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invitations_account_id_foreign` (`account_id`),
  KEY `invitations_invited_by_user_id_foreign` (`invited_by_user_id`),
  CONSTRAINT `invitations_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invitations_invited_by_user_id_foreign` FOREIGN KEY (`invited_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `invitations` DISABLE KEYS */;
/*!40000 ALTER TABLE `invitations` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_reserved_at_index` (`queue`,`reserved_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `journal_entries` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `date` datetime NOT NULL,
  `journalable_id` int NOT NULL,
  `journalable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `journal_entries_account_id_foreign` (`account_id`),
  CONSTRAINT `journal_entries_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `journal_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `journal_entries` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `life_event_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `default_life_event_category_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `core_monica_data` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `life_event_categories_account_id_foreign` (`account_id`),
  CONSTRAINT `life_event_categories_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `life_event_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `life_event_categories` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `life_event_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `life_event_category_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `default_life_event_type_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `core_monica_data` tinyint(1) NOT NULL DEFAULT '0',
  `specific_information_structure` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `life_event_types_account_id_foreign` (`account_id`),
  KEY `life_event_types_life_event_category_id_foreign` (`life_event_category_id`),
  CONSTRAINT `life_event_types_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `life_event_types_life_event_category_id_foreign` FOREIGN KEY (`life_event_category_id`) REFERENCES `life_event_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `life_event_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `life_event_types` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `life_events` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `contact_id` int unsigned NOT NULL,
  `life_event_type_id` int unsigned NOT NULL,
  `reminder_id` int unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` mediumtext COLLATE utf8mb4_unicode_ci,
  `happened_at` datetime NOT NULL,
  `happened_at_month_unknown` tinyint(1) NOT NULL DEFAULT '0',
  `happened_at_day_unknown` tinyint(1) NOT NULL DEFAULT '0',
  `specific_information` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `life_events_account_id_foreign` (`account_id`),
  KEY `life_events_contact_id_foreign` (`contact_id`),
  KEY `life_events_life_event_type_id_foreign` (`life_event_type_id`),
  KEY `life_events_reminder_id_foreign` (`reminder_id`),
  CONSTRAINT `life_events_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `life_events_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `life_events_life_event_type_id_foreign` FOREIGN KEY (`life_event_type_id`) REFERENCES `life_event_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `life_events_reminder_id_foreign` FOREIGN KEY (`reminder_id`) REFERENCES `reminders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `life_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `life_events` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `contact_id` int unsigned NOT NULL,
  `conversation_id` int unsigned NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `written_at` datetime NOT NULL,
  `written_by_me` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_conversation_id_foreign` (`conversation_id`),
  KEY `messages_account_id_foreign` (`account_id`),
  KEY `messages_contact_id_foreign` (`contact_id`),
  CONSTRAINT `messages_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `metadata_love_relationships` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `relationship_id` int unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  `notes` mediumtext COLLATE utf8mb4_unicode_ci,
  `meet_date` datetime DEFAULT NULL,
  `official_date` datetime DEFAULT NULL,
  `breakup_date` datetime DEFAULT NULL,
  `breakup_reason` mediumtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `metadata_love_relationships_account_id_foreign` (`account_id`),
  KEY `metadata_love_relationships_relationship_id_foreign` (`relationship_id`),
  CONSTRAINT `metadata_love_relationships_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `metadata_love_relationships_relationship_id_foreign` FOREIGN KEY (`relationship_id`) REFERENCES `relationships` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `metadata_love_relationships` DISABLE KEYS */;
/*!40000 ALTER TABLE `metadata_love_relationships` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=263 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2014_10_12_000000_create_users_table',1),(2,'2014_10_12_100000_create_password_resets_table',1),(3,'2016_06_01_000001_create_oauth_auth_codes_table',1),(4,'2016_06_01_000002_create_oauth_access_tokens_table',1),(5,'2016_06_01_000003_create_oauth_refresh_tokens_table',1),(6,'2016_06_01_000004_create_oauth_clients_table',1),(7,'2016_06_01_000005_create_oauth_personal_access_clients_table',1),(8,'2016_06_07_234741_create_account_table',1),(9,'2016_06_08_003006_add_account_info_table',1),(10,'2016_06_08_005413_create_contacts_table',1),(11,'2016_06_25_224219_create_reminder_type_table',1),(12,'2016_06_28_191025_create_tasks_table',1),(13,'2016_06_30_185050_create_notes_table',1),(14,'2016_07_25_133835_add_width_field',1),(15,'2016_08_28_122938_create_kids_table',1),(16,'2016_08_28_215159_create_relations_table',1),(17,'2016_09_03_202027_add_reminder_id_to_contacts',1),(18,'2016_09_05_134937_add_last_talked_to_field',1),(19,'2016_09_05_135927_add_people_id_to_contacts',1),(20,'2016_09_05_145111_add_name_info_to_peoples',1),(21,'2016_09_06_213550_create_activity_type_table',1),(22,'2016_09_10_164406_create_jobs_table',1),(23,'2016_09_10_170122_create_notifications_table',1),(24,'2016_09_12_014120_create_failed_jobs_table',1),(25,'2016_09_30_014720_add_kid_to_reminder',1),(26,'2016_10_15_024156_add_deleted_at_to_users',1),(27,'2016_10_19_155139_create_cache_table',1),(28,'2016_10_19_155800_create_sessions_table',1),(29,'2016_10_21_022941_add_statistics_table',1),(30,'2016_10_24_013543_add_journal_setting_to_users',1),(31,'2016_10_24_014257_create_journal_tables',1),(32,'2016_10_28_002518_add_metric_to_settings',1),(33,'2016_11_01_014353_create_activities_table',1),(34,'2016_11_01_015957_add_icon_column',1),(35,'2016_11_03_150307_add_activity_location_to_activities',1),(36,'2016_11_09_013049_add_events_table',1),(37,'2016_12_08_011555_remove_type_from_notes',1),(38,'2016_12_13_133945_add_gifts_table',1),(39,'2016_12_28_150831_change_title_column',1),(40,'2017_01_14_200815_add_facebook_columns_to_users_table',1),(41,'2017_01_15_045025_add_colors_to_users',1),(42,'2017_01_22_142645_add_fields_to_contacts',1),(43,'2017_01_23_043831_change_people_to_contact_for_kids',1),(44,'2017_01_26_013524_change_people_to_significantother',1),(45,'2017_01_26_022852_change_notes_to_contact',1),(46,'2017_01_26_034553_add_notes_count_to_contact',1),(47,'2017_01_27_024356_change_people_in_events',1),(48,'2017_01_28_180156_remove_deleted_at_from_significant_others',1),(49,'2017_01_28_184901_remove_deleted_at_from_kids',1),(50,'2017_01_28_193913_remove_deleted_at_from_notes',1),(51,'2017_01_28_222114_remove_viewed_at_from_contacts',1),(52,'2017_01_29_175146_remove_delete_at_from_activities',1),(53,'2017_01_29_175629_add_number_activities_to_contacts',1),(54,'2017_01_31_025849_add_activity_statistics_table',1),(55,'2017_02_02_232450_add_confirmation',1),(56,'2017_02_04_225618_change_reminders_table',1),(57,'2017_02_05_035925_add_gifts_metrics_to_contacts',1),(58,'2017_02_05_041740_change_gifts_table',1),(59,'2017_02_05_042122_change_people_to_contact_for_gifts',1),(60,'2017_02_07_041607_change_tasks_table',1),(61,'2017_02_07_051355_add_number_tasks_to_contact',1),(62,'2017_02_08_002251_change_number_tasks_contact',1),(63,'2017_02_08_025358_add_sort_preferences_to_users',1),(64,'2017_02_10_195613_remove_notifications_table',1),(65,'2017_02_10_214714_remove_people_table',1),(66,'2017_02_10_215405_remove_entities_table',1),(67,'2017_02_10_215705_remove_deleted_at_from_contact',1),(68,'2017_02_10_224355_calculate_statistics',1),(69,'2017_02_11_154900_add_avatars_to_contacts',1),(70,'2017_02_12_134220_create_entries_table',1),(71,'2017_05_03_155254_move_significant_other_data',1),(72,'2017_05_04_164723_remove_contact_encryption',1),(73,'2017_05_04_185921_add_title_to_activities',1),(74,'2017_05_04_193252_alter_activity_nullable',1),(75,'2017_05_08_164514_remove_encryption_tasks',1),(76,'2017_05_30_002239_remove_predefined_reminders',1),(77,'2017_05_30_023116_create_money_table',1),(78,'2017_06_07_173437_add_multiple_genders_choices',1),(79,'2017_06_10_152945_add_social_networks_to_contacts',1),(80,'2017_06_10_155349_create_currencies_data',1),(81,'2017_06_11_025227_remove_encryption_journal',1),(82,'2017_06_11_110735_change_unique_constraint_for_contacts',1),(83,'2017_06_13_035059_remove_gifts_encryption',1),(84,'2017_06_13_195740_add_company_to_contacts',1),(85,'2017_06_14_131803_remove_bern_timezone',1),(86,'2017_06_14_132911_add_zar_currency_to_currencies_table',1),(87,'2017_06_16_215256_add_about_who_to_reminders',1),(88,'2017_06_17_010900_fix_contacts_table',1),(89,'2017_06_17_153814_refactor_user_table',1),(90,'2017_06_19_105842_add_stripe_fields_to_users',1),(91,'2017_06_20_121345_add_invitations_statistics',1),(92,'2017_06_22_210813_add_name_order_to_users',1),(93,'2017_06_27_134704_create_import_table',1),(94,'2017_06_29_211725_add_import_job_to_statistics',1),(95,'2017_06_29_230523_add_gravatar_url_to_users',1),(96,'2017_07_02_155736_create_tags_table',1),(97,'2017_07_04_132743_add_tags_to_statistics',1),(98,'2017_07_09_164312_update_bad_translation_key',1),(99,'2017_07_12_014244_create_calls_table',1),(100,'2017_07_17_005012_drop_reminders_count_from_contacts',1),(101,'2017_07_18_215312_add_danish_kroner_to_currencies_table',1),(102,'2017_07_18_215758_add_indian_rupee_to_currencies_table',1),(103,'2017_07_19_094503_add_brazilian_real_to_currencies',1),(104,'2017_07_22_153209_create_instance_table',1),(105,'2017_07_26_220021_change_contacts_table',1),(106,'2017_08_02_152838_change_string_to_boolean_for_reminders',1),(107,'2017_08_06_085629_change_events_data',1),(108,'2017_08_06_153253_move_kids_to_contacts',1),(109,'2017_08_16_041431_add_contact_avatar_location',1),(110,'2017_08_21_224835_remove_paid_limitations_for_current_users',1),(111,'2017_09_10_125918_remove_unusued_counters',1),(112,'2017_09_13_095923_add_tracking_table',1),(113,'2017_09_13_191714_add_partial_notion',1),(114,'2017_10_14_083556_change_gift_column_structure',1),(115,'2017_10_17_170803_change_gift_structure',1),(116,'2017_10_19_134816_create_activity_contact_table',1),(117,'2017_10_19_135215_move_activities_to_pivot_table',1),(118,'2017_10_25_102923_remove_contact_id_activities_table',1),(119,'2017_11_01_122541_add_met_through_to_contacts',1),(120,'2017_11_02_202601_add_is_dead_to_contacts',1),(121,'2017_11_10_174654_create_contact_fields_table',1),(122,'2017_11_10_181043_migrate_contacts_information',1),(123,'2017_11_10_202620_move_addresses_from_contact_to_addresses',1),(124,'2017_11_10_204035_delete_contact_fields_from_contacts',1),(125,'2017_11_20_115635_change-amount-to-double-on-debts',1),(126,'2017_11_27_083043_add_more_statistics',1),(127,'2017_11_27_134403_add_new_avatar_to_contacts',1),(128,'2017_11_27_202857_change_tasks_table_structure',1),(129,'2017_12_01_113748_update_notes',1),(130,'2017_12_04_164831_create_ages_table',1),(131,'2017_12_04_165421_move_ages_data',1),(132,'2017_12_10_181535_remove_important_dates_table',1),(133,'2017_12_10_205328_add_account_id_to_activities',1),(134,'2017_12_10_214545_add_last_consulted_at_to_contacts',1),(135,'2017_12_13_115857_create_day_table',1),(136,'2017_12_21_163616_update_journal_entries_with_existing_activities',1),(137,'2017_12_21_170327_add_google2fa_secret_to_users',1),(138,'2017_12_24_115641_create_pets_table',1),(139,'2017_12_31_114224_add_dashboard_tab_to_users',1),(140,'2018_01_15_105858_create_additional_reminders_table',1),(141,'2018_01_16_203358_add_gift_received',1),(142,'2018_01_16_212320_rename_gift_columns',1),(143,'2018_01_17_230820_add_gift_tab_view_to_users',1),(144,'2018_01_27_014146_add_custom_gender',1),(145,'2018_02_25_202752_change_locale_in_db',1),(146,'2018_02_28_223747_update_notification_table',1),(147,'2018_03_03_204440_create_relationship_type_table',1),(148,'2018_03_18_085815_populate_default_relationship_type_tables',1),(149,'2018_03_18_090209_populate_relationship_type_tables_with_default_values',1),(150,'2018_03_18_090345_migrate_current_relationship_table_to_new_relationship_structure',1),(151,'2018_03_24_083258_migrate_offsprings',1),(152,'2018_04_04_220850_create_default_modules_table',1),(153,'2018_04_04_222608_create_account_modules_table',1),(154,'2018_04_10_205655_fix_production_error',1),(155,'2018_04_10_222515_migrate-modules',1),(156,'2018_04_13_131008_fix-contacts-data',1),(157,'2018_04_13_205231_create_changes_table',1),(158,'2018_04_14_081052_fix_wrong_gender',1),(159,'2018_04_19_190239_stay_in_touch',1),(160,'2018_05_06_061227_external_countries',1),(161,'2018_05_06_194710_delete_reminder_sent_table',1),(162,'2018_05_07_070458_create_terms_table',1),(163,'2018_05_13_110706_add_ex_wife_husband_relationship',1),(164,'2018_05_16_143631_add_nickname_to_contacts',1),(165,'2018_05_16_214222_add_timestamps_to_currencies',1),(166,'2018_05_20_121028_accept_terms',1),(167,'2018_05_20_225034_change_name_order_user-_preferencies',1),(168,'2018_05_24_160546_fix-inconsistant-reminder-time',1),(169,'2018_06_10_191450_add_love_metadata_relationshisp',1),(170,'2018_06_10_221746_migrate_entries_objects',1),(171,'2018_06_11_184017_change_default_user_table',1),(172,'2018_06_13_000100_create_u2f_key_table',1),(173,'2018_06_14_212502_change_default_name_order_user_table',1),(174,'2018_07_03_204220_create_default_activity_type_groups_table',1),(175,'2018_07_08_104306_update-timestamps-timezone',1),(176,'2018_07_26_104306_create-conversations',1),(177,'2018_08_06_145046_add_starred_to_contacts',1),(178,'2018_08_09_18000_fix-empty-reminder-time',1),(179,'2018_08_18_180426_add_legacy_free_plan',1),(180,'2018_08_29_124804_add_conversations_to_statistics',1),(181,'2018_08_29_222051_add_conversations_to_modules',1),(182,'2018_08_31_020908_create_life_events_table',1),(183,'2018_09_02_150531_contact_archiving',1),(184,'2018_09_05_025008_add_default_profile_view',1),(185,'2018_09_05_213507_mark_modules_migrated',1),(186,'2018_09_13_135926_add_description_field_to_contacts',1),(187,'2018_09_18_142844_remove_events',1),(188,'2018_09_23_024528_add_documents_table',1),(189,'2018_09_29_114125_add_reminder_to_life_events',1),(190,'2018_10_01_211757_add_number_of_views',1),(191,'2018_10_04_181116_life_event_vehicle',1),(192,'2018_10_07_120133_fix_json_column',1),(193,'2018_10_16_000703_add_documents_to_module_table',1),(194,'2018_10_19_081816_life_event_tattoo',1),(195,'2018_10_27_230346_fix_non_english_tab_slugs',1),(196,'2018_10_28_165814_email_verified',1),(197,'2018_11_11_145035_remove_changelogs_table',1),(198,'2018_11_15_172333_make_contact_id_nullable_in_tasks',1),(199,'2018_11_18_021908_create_images_table',1),(200,'2018_11_21_212932_add_contacts_uuid',1),(201,'2018_11_25_020818_add_contact_photo_table',1),(202,'2018_11_30_154729_recovery_codes',1),(203,'2018_12_08_233140_add_who_called_to_calls',1),(204,'2018_12_09_023232_add_emotions_table',1),(205,'2018_12_09_145956_create_emotion_call_table',1),(206,'2018_12_16_195440_add_gps_coordinates_to_addressess',1),(207,'2018_12_19_002819_create_places_table',1),(208,'2018_12_19_003444_move_addresses_data',1),(209,'2018_12_21_235418_add_weather_table',1),(210,'2018_12_22_021123_add_weather_preferences_to_users',1),(211,'2018_12_22_200413_add_reminder_initial_date_to_reminders',1),(212,'2018_12_24_164256_add_companies_table',1),(213,'2018_12_24_220019_add_occupations_table',1),(214,'2018_12_25_001736_add_linkedin_to_default_contact_field_type',1),(215,'2018_12_25_012011_move_linkedin_data_to_contact_field_type',1),(216,'2018_12_29_091017_default_temperature_scale',1),(217,'2018_12_29_135516_sync_token',1),(218,'2019_01_05_152329_add_reminder_ids_to_contacts',1),(219,'2019_01_05_152405_migrate_previous_remiders',1),(220,'2019_01_05_152456_drop_special_date_id_from_reminders',1),(221,'2019_01_05_152526_schedule_new_reminders',1),(222,'2019_01_05_202557_add_foreign_keys_to_reminder',1),(223,'2019_01_05_202748_add_foreign_key_to_reminder_rule',1),(224,'2019_01_05_202938_add_foreign_key_to_contacts',1),(225,'2019_01_05_203201_add_foreign_key_for_reminder_in_life-events_table',1),(226,'2019_01_06_135133_update_u2f_key_table',1),(227,'2019_01_06_150143_add_inactive_flag_to_reminders',1),(228,'2019_01_06_190036_u2f_key_name',1),(229,'2019_01_11_142944_add_foreign_keys_to_activities',1),(230,'2019_01_11_183717_change_activities_date_type',1),(231,'2019_01_17_093812_add_admin_user',1),(232,'2019_01_18_142032_add_dav_uuid',1),(233,'2019_01_22_034555_create_emotion_activity_table',1),(234,'2019_01_24_221539_change_activity_model_location',1),(235,'2019_01_31_223600_add_swiss_chf_to_currencies_table',1),(236,'2019_02_08_234959_remove_users_without_account',1),(237,'2019_02_09_200203_add_gender_type',1),(238,'2019_02_17_112452_add_default_gender',1),(239,'2019_02_20_205744_allow_gender_null',1),(240,'2019_02_24_223855_remove_relation_type_name',1),(241,'2019_03_27_103012_set_default_profile_links',1),(242,'2019_03_29_163611_add_webauthn',1),(243,'2019_05_05_194746_add_cron_schedule',1),(244,'2019_05_15_205533_rename_preferences',1),(245,'2019_05_26_000000_add_relationship_table_indexes',1),(246,'2019_05_27_000000_populate_relationship_type_tables_with_stepparent_values',1),(247,'2019_08_12_213308_change_avatars_structure',1),(248,'2019_08_12_222938_create_avatars_for_existing_contacts',1),(249,'2019_08_13_160332_add_me_contact_on_user',1),(250,'2019_08_14_091427_update_stripe_columns',1),(251,'2019_09_04_075311_fix_tattoo_or_piercing_translation',1),(252,'2019_12_17_024553_add_foreign_keys',1),(253,'2019_12_21_100315_change_gift_status',1),(254,'2019_12_21_194559_add_photo_gift',1),(255,'2019_12_27_23533_rename_picnicked',1),(256,'2020_02_03_015403_create_audit_log_table',1),(257,'2020_02_18_211620_add_contact_field_label',1),(258,'2020_03_22_132429_rename_birthday_reminder_title_deceased',1),(259,'2020_04_24_185810_remove_duplicate_currency',1),(260,'2020_04_24_205810_currencies_table_seed',1),(261,'2020_04_24_212138_update_amount_format',1),(262,'2020_05_08_072433_google2fa_column_size',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `modules` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `translation_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `delible` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `modules_account_id_foreign` (`account_id`),
  CONSTRAINT `modules_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `modules` DISABLE KEYS */;
/*!40000 ALTER TABLE `modules` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `contact_id` int unsigned NOT NULL,
  `body` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_favorited` tinyint(1) NOT NULL DEFAULT '0',
  `favorited_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notes_account_id_foreign` (`account_id`),
  KEY `notes_contact_id_foreign` (`contact_id`),
  CONSTRAINT `notes_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notes_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `notes` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_access_tokens` (
  `id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `client_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scopes` text COLLATE utf8mb4_unicode_ci,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_access_tokens_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `oauth_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_access_tokens` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_auth_codes` (
  `id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `client_id` bigint unsigned NOT NULL,
  `scopes` text COLLATE utf8mb4_unicode_ci,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_auth_codes_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `oauth_auth_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_auth_codes` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_clients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `redirect` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `personal_access_client` tinyint(1) NOT NULL,
  `password_client` tinyint(1) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_clients_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `oauth_clients` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_clients` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_personal_access_clients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `oauth_personal_access_clients` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_personal_access_clients` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_refresh_tokens` (
  `id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_token_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `oauth_refresh_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_refresh_tokens` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `occupations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `contact_id` int unsigned NOT NULL,
  `company_id` int unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salary` int DEFAULT NULL,
  `salary_unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currently_works_here` tinyint(1) DEFAULT '0',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `occupations_account_id_foreign` (`account_id`),
  KEY `occupations_contact_id_foreign` (`contact_id`),
  KEY `occupations_company_id_foreign` (`company_id`),
  CONSTRAINT `occupations_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `occupations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `occupations_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `occupations` DISABLE KEYS */;
/*!40000 ALTER TABLE `occupations` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL,
  KEY `password_resets_email_index` (`email`),
  KEY `password_resets_token_index` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pet_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_common` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `pet_categories` DISABLE KEYS */;
INSERT INTO `pet_categories` VALUES (1,'reptile',0,NULL,NULL),(2,'bird',0,NULL,NULL),(3,'cat',1,NULL,NULL),(4,'dog',1,NULL,NULL),(5,'fish',1,NULL,NULL),(6,'hamster',0,NULL,NULL),(7,'horse',0,NULL,NULL),(8,'rabbit',0,NULL,NULL),(9,'rat',0,NULL,NULL),(10,'small_animal',0,NULL,NULL),(11,'other',0,NULL,NULL);
/*!40000 ALTER TABLE `pet_categories` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `contact_id` int unsigned NOT NULL,
  `pet_category_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pets_account_id_foreign` (`account_id`),
  KEY `pets_contact_id_foreign` (`contact_id`),
  KEY `pets_pet_category_id_foreign` (`pet_category_id`),
  CONSTRAINT `pets_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pets_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pets_pet_category_id_foreign` FOREIGN KEY (`pet_category_id`) REFERENCES `pet_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `pets` DISABLE KEYS */;
/*!40000 ALTER TABLE `pets` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `photos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `new_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filesize` int DEFAULT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `photos_account_id_foreign` (`account_id`),
  CONSTRAINT `photos_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `photos` DISABLE KEYS */;
/*!40000 ALTER TABLE `photos` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `places` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `street` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` char(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `places_account_id_foreign` (`account_id`),
  CONSTRAINT `places_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `places` DISABLE KEYS */;
/*!40000 ALTER TABLE `places` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recovery_codes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `recovery` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recovery_codes_account_id_foreign` (`account_id`),
  KEY `recovery_codes_user_id_foreign` (`user_id`),
  CONSTRAINT `recovery_codes_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recovery_codes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `recovery_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `recovery_codes` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `relationship_type_groups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `delible` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `relationship_type_groups_account_id_name_index` (`account_id`,`name`),
  CONSTRAINT `relationship_type_groups_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `relationship_type_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `relationship_type_groups` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `relationship_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_reverse_relationship` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `relationship_type_group_id` int unsigned NOT NULL,
  `delible` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `relationship_types_account_id_foreign` (`account_id`),
  KEY `relationship_types_relationship_type_group_id_foreign` (`relationship_type_group_id`),
  CONSTRAINT `relationship_types_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `relationship_types_relationship_type_group_id_foreign` FOREIGN KEY (`relationship_type_group_id`) REFERENCES `relationship_type_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `relationship_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `relationship_types` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `relationships` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `relationship_type_id` int unsigned NOT NULL,
  `contact_is` int unsigned NOT NULL,
  `of_contact` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `relationships_account_id_foreign` (`account_id`),
  KEY `relationships_relationship_type_id_foreign` (`relationship_type_id`),
  KEY `relationships_contact_is_foreign` (`contact_is`),
  KEY `relationships_of_contact_foreign` (`of_contact`),
  CONSTRAINT `relationships_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `relationships_contact_is_foreign` FOREIGN KEY (`contact_is`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `relationships_of_contact_foreign` FOREIGN KEY (`of_contact`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `relationships_relationship_type_id_foreign` FOREIGN KEY (`relationship_type_id`) REFERENCES `relationship_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `relationships` DISABLE KEYS */;
/*!40000 ALTER TABLE `relationships` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reminder_outbox` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `reminder_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `planned_date` date NOT NULL,
  `nature` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reminder',
  `notification_number_days_before` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reminder_outbox_account_id_foreign` (`account_id`),
  KEY `reminder_outbox_reminder_id_foreign` (`reminder_id`),
  KEY `reminder_outbox_user_id_foreign` (`user_id`),
  CONSTRAINT `reminder_outbox_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reminder_outbox_reminder_id_foreign` FOREIGN KEY (`reminder_id`) REFERENCES `reminders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reminder_outbox_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `reminder_outbox` DISABLE KEYS */;
/*!40000 ALTER TABLE `reminder_outbox` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reminder_rules` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `number_of_days_before` int NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reminder_rules_account_id_foreign` (`account_id`),
  CONSTRAINT `reminder_rules_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `reminder_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `reminder_rules` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reminder_sent` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `reminder_id` int unsigned DEFAULT NULL,
  `user_id` int unsigned NOT NULL,
  `planned_date` date NOT NULL,
  `sent_date` datetime NOT NULL,
  `nature` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reminder',
  `frequency_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frequency_number` int DEFAULT NULL,
  `html_content` longtext COLLATE utf8mb4_unicode_ci,
  `text_content` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reminder_sent_account_id_foreign` (`account_id`),
  KEY `reminder_sent_reminder_id_foreign` (`reminder_id`),
  KEY `reminder_sent_user_id_foreign` (`user_id`),
  CONSTRAINT `reminder_sent_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reminder_sent_reminder_id_foreign` FOREIGN KEY (`reminder_id`) REFERENCES `reminders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reminder_sent_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `reminder_sent` DISABLE KEYS */;
/*!40000 ALTER TABLE `reminder_sent` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reminders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `contact_id` int unsigned NOT NULL,
  `initial_date` date NOT NULL,
  `title` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `frequency_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequency_number` int DEFAULT NULL,
  `delible` tinyint(1) NOT NULL DEFAULT '1',
  `inactive` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reminders_account_id_foreign` (`account_id`),
  KEY `reminders_contact_id_foreign` (`contact_id`),
  CONSTRAINT `reminders_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reminders_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `reminders` DISABLE KEYS */;
/*!40000 ALTER TABLE `reminders` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  UNIQUE KEY `sessions_id_unique` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `special_dates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `contact_id` int unsigned NOT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_age_based` tinyint(1) NOT NULL DEFAULT '0',
  `is_year_unknown` tinyint(1) NOT NULL DEFAULT '0',
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `special_dates_account_id_uuid_index` (`account_id`,`uuid`),
  KEY `special_dates_contact_id_foreign` (`contact_id`),
  CONSTRAINT `special_dates_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `special_dates_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `special_dates` DISABLE KEYS */;
/*!40000 ALTER TABLE `special_dates` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `statistics` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `number_of_users` int NOT NULL,
  `number_of_contacts` int NOT NULL,
  `number_of_notes` int NOT NULL,
  `number_of_oauth_access_tokens` int NOT NULL,
  `number_of_oauth_clients` int NOT NULL,
  `number_of_offsprings` int NOT NULL,
  `number_of_progenitors` int NOT NULL,
  `number_of_relationships` int NOT NULL,
  `number_of_subscriptions` int NOT NULL,
  `number_of_reminders` int NOT NULL,
  `number_of_tasks` int NOT NULL,
  `number_of_kids` int NOT NULL,
  `number_of_activities` int NOT NULL,
  `number_of_addresses` int NOT NULL,
  `number_of_api_calls` int NOT NULL,
  `number_of_calls` int NOT NULL,
  `number_of_contact_fields` int NOT NULL,
  `number_of_contact_field_types` int NOT NULL,
  `number_of_debts` int NOT NULL,
  `number_of_entries` int NOT NULL,
  `number_of_gifts` int NOT NULL,
  `number_of_invitations_sent` int DEFAULT NULL,
  `number_of_accounts_with_more_than_one_user` int DEFAULT NULL,
  `number_of_tags` int DEFAULT NULL,
  `number_of_import_jobs` int DEFAULT NULL,
  `number_of_conversations` int DEFAULT NULL,
  `number_of_messages` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `statistics` DISABLE KEYS */;
/*!40000 ALTER TABLE `statistics` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_plan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscriptions_account_id_stripe_status_index` (`account_id`,`stripe_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `synctoken` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'contacts',
  `timestamp` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `synctoken_user_id_foreign` (`user_id`),
  KEY `synctoken_account_id_user_id_name_index` (`account_id`,`user_id`,`name`),
  CONSTRAINT `synctoken_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `synctoken_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `synctoken` DISABLE KEYS */;
/*!40000 ALTER TABLE `synctoken` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tags_account_id_foreign` (`account_id`),
  CONSTRAINT `tags_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `contact_id` int unsigned DEFAULT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `completed` tinyint(1) NOT NULL DEFAULT '0',
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_account_id_uuid_index` (`account_id`,`uuid`),
  KEY `tasks_contact_id_foreign` (`contact_id`),
  CONSTRAINT `tasks_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `term_user` (
  `account_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `term_id` int unsigned NOT NULL,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  KEY `term_user_account_id_foreign` (`account_id`),
  KEY `term_user_user_id_foreign` (`user_id`),
  KEY `term_user_term_id_foreign` (`term_id`),
  CONSTRAINT `term_user_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `term_user_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `term_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `term_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `term_user` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `terms` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `term_version` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `term_content` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `privacy_version` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `privacy_content` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `terms` DISABLE KEYS */;
INSERT INTO `terms` VALUES (1,'2','\nScope of service\nMonica supports the following browsers:\n\nInternet Explorer (11+)\nFirefox (50+)\nChrome (latest)\nSafari (latest)\nI do not guarantee that the site will work with other browsers, but it’s very likely that it will just work.\n\nRights\nYou don’t have to provide your real name when you register to an account. You do however need a valid email address if you want to upgrade your account to the paid version, or receive reminders by email.\n\nYou have the right to close your account at any time.\n\nYou have the right to export your data at any time, in the SQL format.\n\nYour data will not be intentionally shown to other users or shared with third parties.\n\nYour personal data will not be shared with anyone without your consent.\n\nYour data is backed up every hour.\n\nIf the site ceases operation, you will receive an opportunity to export all your data before the site dies.\n\nAny new features that affect privacy will be strictly opt-in.\n\nResponsibilities\nYou will not use the site to store illegal information or data under the Canadian law (or any law).\n\nYou have to be at least 18+ to create an account and use the site.\n\nYou must not abuse the site by knowingly posting malicious code that could harm you or the other users.\n\nYou must only use the site to do things that are widely accepted as morally good.\n\nYou may not make automated requests to the site.\n\nYou may not abuse the invitation system.\n\nYou are responsible for keeping your account secure.\n\nI reserve the right to close accounts that abuse the system (thousands of contacts with hundred of thousands of reminders for instance) or use it in an unreasonable manner.\n\nOther important legal stuff\nThough I want to provide a great service, there are certain things about the service I cannot promise. For example, the services and software are provided “as-is”, at your own risk, without express or implied warranty or condition of any kind. I also disclaim any warranties of merchantability, fitness for a particular purpose or non-infringement. Monica will have no responsibility for any harm to your computer system, loss or corruption of data, or other harm that results from your access to or use of the Services or Software.\n\nThese Terms can change at any time, but I’ll never be a dick about it. Running this site is a dream come true to me, and I hope I’ll be able to run it as long as I can.\n        ','2','\nMonica is an open source project. The hosted version has a premium plan that let us collect money so we can pay for the servers and additional servers, but the main goal is not to make money (otherwise we wouldn’t have opened source it).\n\nMonica comes in two flavors: you can either use our hosted version, or download it and run it yourself. In the latter case, we do not track anything at all. We don’t know that you’ve even downloaded the product. Do whatever you want with it (but respect your local laws).\n\nWhen you create your account on our hosted version, you are giving the site information about yourself that we collect. This includes your name, your email address and your password, that is encrypted before being stored. We do not store any other personal information.\n\nWhen you login to the service, we are using cookies to remember your login credentials. This is the only use we do with the cookies.\n\nMonica runs on Linode and we are the only ones, apart from Linode’s employees, who have access to those servers.\n\nWe do hourly backups of the database.\n\nYour password is encrypted with bcrypt, a password hashing algorithm that is highly secure. You can also activate two factor authentication on your account if you need an extra layer of security. Apart from those encryptions mechanism, your data is not encrypted in the database. If someone gets access to the database, they will be able to read your data. We do our best to make sure that this will never happen, but it can happen.\n\nIf a data breach happens, we will contact the users who are affected to warn them about the breach.\n\nTransactional emails are dserved through Postmark.\n\nWe use an open source tool called Sentry to track errors that happen in production. Their service records the errors, but they don’t have access to any information apart the account ID, which lets me debug what’s going on.\n\nThe site does not currently and will never show ads. It also does not, and don’t intend to, sell data to a third party, with or without your consent. We are just against this. Fuck ads.\n\nWe do no use any tracking third parties, like Google Analytics or Intercom, that track user behaviours or data, neither on the marketing site or the hosted version. We are deeply against their principles as they would use those data to profile you, which we are totally against.\n\nAll the data you put on Monica belongs to you. We do not have any rights on it. Please don’t put illegal stuff on it, otherwise we’d be in trouble.\n\nAll the information about the contacts you put on Monica are private to you. We do not cross link information between accounts or use one information in an account to populate another account (unlike Facebook for instance).\n\nWe use Stripe to collect payments made to access the paid version. We do not store credit card information or anything concerning the transactions themselves on our servers. However, as per the open source library we use to process the payments (Laravel Cashier), we store the last 4 digits of the credit card, the brand name (VISA or MasterCard). As a user, you are identified on Stripe by a random number that they generate and use.\n\nRegarding the payments, you can downgrade to the free plan whenever you like. When you do, Stripe is automatically updated and we have no way to charge you again, even if we would like to. The less we deal with payment information, the happier we are.\n\nYou can export your data at any time. You can also use the API to export all your data if you know how to do it. You can also request that we process this ourselves and send it to you. Your data will be exported in the SQL format.\n\nWhen you close your account, we immediately destroy all your personal information and don’t keep any backup. While you have control over this, we can delete an account for you if you ask us.\n\nIn certain situations, we may be required to disclose peronal data in response to lawful requests by public authorities, including to met national security or law enforcements requirements. We just hope that this never happens.\n\nIf you violate the terms of use we will terminate your account and notify you about it. However if you follow the \"don’t be a dick\" policy, nothing should ever happen to you and we’ll all be happy.\n\nMonica uses only open-source projects that are mainly hosted on Github.\n\nWe will update this privacy policy as soon as we introduce new information practices. If we do, we will send an email to the email address specified in your account. We will never be a dick about it and will never, ever, introduce something in what we do that will affect your right to the absolute privacy.','2018-04-11 22:00:00',NULL);
/*!40000 ALTER TABLE `terms` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `u2f_key` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'key',
  `user_id` int unsigned NOT NULL,
  `keyHandle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `publicKey` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `certificate` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `counter` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u2f_key_publickey_unique` (`publicKey`),
  KEY `u2f_key_user_id_foreign` (`user_id`),
  CONSTRAINT `u2f_key_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `u2f_key` DISABLE KEYS */;
/*!40000 ALTER TABLE `u2f_key` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `me_contact_id` int unsigned DEFAULT NULL,
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google2fa_secret` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_id` int unsigned NOT NULL,
  `timezone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency_id` int unsigned DEFAULT '2',
  `locale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `metric` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fahrenheit',
  `fluid_container` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'false',
  `contacts_sort_order` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'firstnameAZ',
  `name_order` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'firstname_lastname_nickname',
  `invited_by_user_id` int unsigned DEFAULT NULL,
  `dashboard_active_tab` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'calls',
  `gifts_active_tab` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ideas',
  `profile_active_tab` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'notes',
  `profile_new_life_event_badge_seen` tinyint(1) NOT NULL DEFAULT '0',
  `temperature_scale` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'celsius',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_me_contact_id_foreign` (`me_contact_id`),
  KEY `users_account_id_foreign` (`account_id`),
  KEY `users_currency_id_foreign` (`currency_id`),
  KEY `users_invited_by_user_id_foreign` (`invited_by_user_id`),
  CONSTRAINT `users_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `users_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_invited_by_user_id_foreign` FOREIGN KEY (`invited_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_me_contact_id_foreign` FOREIGN KEY (`me_contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `weather` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `place_id` int unsigned NOT NULL,
  `weather_json` varchar(2000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `weather_account_id_foreign` (`account_id`),
  KEY `weather_place_id_foreign` (`place_id`),
  CONSTRAINT `weather_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `weather_place_id_foreign` FOREIGN KEY (`place_id`) REFERENCES `places` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `weather` DISABLE KEYS */;
/*!40000 ALTER TABLE `weather` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webauthn_keys` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'key',
  `credentialId` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transports` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attestationType` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `trustPath` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `aaguid` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `credentialPublicKey` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `counter` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `webauthn_keys_user_id_foreign` (`user_id`),
  KEY `webauthn_keys_credentialid_index` (`credentialId`),
  CONSTRAINT `webauthn_keys_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `webauthn_keys` DISABLE KEYS */;
/*!40000 ALTER TABLE `webauthn_keys` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

