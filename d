diff --git a/app/Console/Commands/MigrateDatabaseCollation.php b/app/Console/Commands/MigrateDatabaseCollation.php
index 1364d29d9..ac45b3b96 100644
--- a/app/Console/Commands/MigrateDatabaseCollation.php
+++ b/app/Console/Commands/MigrateDatabaseCollation.php
@@ -2,7 +2,6 @@
 
 namespace App\Console\Commands;
 
-use App\Helpers\DBHelper;
 use Illuminate\Console\Command;
 use Illuminate\Support\Facades\DB;
 use Illuminate\Console\ConfirmableTrait;
@@ -35,7 +34,7 @@ class MigrateDatabaseCollation extends Command
     {
         if ($this->confirmToProceed()) {
             try {
-                $connection = DBHelper::connection();
+                $connection = DB::connection();
 
                 if ($connection->getDriverName() != 'mysql') {
                     return;
diff --git a/app/Console/Commands/SetupFrontEndTest.php b/app/Console/Commands/SetupFrontEndTest.php
index 97bb354b1..d70eb0869 100644
--- a/app/Console/Commands/SetupFrontEndTest.php
+++ b/app/Console/Commands/SetupFrontEndTest.php
@@ -2,7 +2,6 @@
 
 namespace App\Console\Commands;
 
-use App\Helpers\DBHelper;
 use App\Models\Account\Account;
 use Illuminate\Console\Command;
 use Illuminate\Support\Facades\DB;
@@ -52,7 +51,7 @@ class SetupFrontEndTest extends Command
      */
     public function handle(): void
     {
-        $connection = DBHelper::connection();
+        $connection = DB::connection();
         if (file_exists('monicadump.sql')) {
             $cmd = 'mysql -u '.$connection->getConfig('username');
             if ($connection->getConfig('password') != '') {
diff --git a/app/Console/Commands/Update.php b/app/Console/Commands/Update.php
index c4bc497e9..ddb6d860e 100644
--- a/app/Console/Commands/Update.php
+++ b/app/Console/Commands/Update.php
@@ -2,7 +2,6 @@
 
 namespace App\Console\Commands;
 
-use App\Helpers\DBHelper;
 use Illuminate\Console\Command;
 use Illuminate\Support\Facades\DB;
 use Illuminate\Support\Facades\Schema;
@@ -112,7 +111,7 @@ class Update extends Command
 
     private function migrateCollationTest()
     {
-        $connection = DBHelper::connection();
+        $connection = DB::connection();
 
         if ($connection->getDriverName() != 'mysql') {
             return false;
diff --git a/app/Helpers/DBHelper.php b/app/Helpers/DBHelper.php
index 187620eee..1d0869b91 100644
--- a/app/Helpers/DBHelper.php
+++ b/app/Helpers/DBHelper.php
@@ -7,15 +7,6 @@ use Illuminate\Support\Facades\DB;
 
 class DBHelper
 {
-    /**
-     * Get connection.
-     *
-     * @return \Illuminate\Database\Connection
-     */
-    public static function connection() {
-        return DB::connection();
-    }
-
     /**
      * Get the version of DB engine.
      *
@@ -24,7 +15,7 @@ class DBHelper
     public static function version()
     {
         try {
-            return static::connection()->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
+            return DB::connection()->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
         } catch (\Exception $e) {
             return;
         }
@@ -52,13 +43,13 @@ class DBHelper
                 FROM information_schema.tables
                 WHERE table_schema = :table_schema
                 AND table_name LIKE :table_prefix', [
-            'table_schema' => static::connection()->getDatabaseName(),
-            'table_prefix' => '%'.static::connection()->getTablePrefix().'%',
+            'table_schema' => DB::connection()->getDatabaseName(),
+            'table_prefix' => '%'.DB::connection()->getTablePrefix().'%',
         ]);
     }
 
     public static function getTable($name)
     {
-        return '`'.static::connection()->getTablePrefix().$name.'`';
+        return '`'.DB::connection()->getTablePrefix().$name.'`';
     }
 }
diff --git a/app/Helpers/StorageHelper.php b/app/Helpers/StorageHelper.php
index 27b4918f8..cba4f8ab3 100644
--- a/app/Helpers/StorageHelper.php
+++ b/app/Helpers/StorageHelper.php
@@ -4,20 +4,9 @@ namespace App\Helpers;
 
 use App\Models\Account\Account;
 use Illuminate\Support\Facades\DB;
-use Illuminate\Support\Facades\Storage;
 
 class StorageHelper
 {
-    /**
-     * Get a filesystem instance.
-     *
-     * @return \Illuminate\Filesystem\FilesystemAdapter
-     */
-    public static function disk($name = null)
-    {
-        return Storage::disk($name);
-    }
-
     /**
      * Get the storage size of the account, in bytes.
      *
diff --git a/app/Helpers/StringHelper.php b/app/Helpers/StringHelper.php
index c14f4591e..425c51b1d 100644
--- a/app/Helpers/StringHelper.php
+++ b/app/Helpers/StringHelper.php
@@ -19,7 +19,7 @@ class StringHelper
         $searchTerms = explode(' ', $searchTerm);
 
         foreach ($searchTerms as $searchTerm) {
-            $searchTerm = DBHelper::connection()->getPdo()->quote('%'.$searchTerm.'%');
+            $searchTerm = DB::connection()->getPdo()->quote('%'.$searchTerm.'%');
 
             foreach ($array as $column) {
                 if ($first) {
diff --git a/app/Http/Controllers/Auth/PasswordChangeController.php b/app/Http/Controllers/Auth/PasswordChangeController.php
index ef556bee2..c6c859908 100644
--- a/app/Http/Controllers/Auth/PasswordChangeController.php
+++ b/app/Http/Controllers/Auth/PasswordChangeController.php
@@ -4,9 +4,11 @@ namespace App\Http\Controllers\Auth;
 
 use App\Models\User\User;
 use Illuminate\Support\Str;
+use App\Http\Requests\Request;
 use App\Http\Controllers\Controller;
 use Illuminate\Support\Facades\Auth;
 use Illuminate\Support\Facades\Hash;
+use Illuminate\Support\Facades\Password;
 use App\Http\Requests\PasswordChangeRequest;
 use Illuminate\Contracts\Auth\Authenticatable;
 use Illuminate\Foundation\Auth\RedirectsUsers;
@@ -95,7 +97,6 @@ class PasswordChangeController extends Controller
      */
     protected function getUser(array $credentials)
     {
-        /** @var User */
         $user = Auth::user();
 
         // Using current email from user, and current password sent with the request to authenticate the user
diff --git a/app/Jobs/ExportAccountAsSQL.php b/app/Jobs/ExportAccountAsSQL.php
index 145127c93..23dca18a6 100644
--- a/app/Jobs/ExportAccountAsSQL.php
+++ b/app/Jobs/ExportAccountAsSQL.php
@@ -3,7 +3,6 @@
 namespace App\Jobs;
 
 use Illuminate\Http\File;
-use App\Helpers\StorageHelper;
 use Illuminate\Support\Facades\Auth;
 use Illuminate\Queue\SerializesModels;
 use Illuminate\Support\Facades\Storage;
@@ -49,7 +48,7 @@ class ExportAccountAsSQL
             $tempFilePath = disk_adapter('local')->getPathPrefix().$tempFileName;
 
             // move the file to the public storage
-            return StorageHelper::disk(self::STORAGE)
+            return Storage::disk(self::STORAGE)
                 ->putFileAs($this->path, new File($tempFilePath), basename($tempFileName));
         } finally {
             // delete old file from temp folder
diff --git a/app/Models/Account/ImportJob.php b/app/Models/Account/ImportJob.php
index bbac425a4..13bc9aa99 100644
--- a/app/Models/Account/ImportJob.php
+++ b/app/Models/Account/ImportJob.php
@@ -16,19 +16,8 @@ use Illuminate\Database\Eloquent\Relations\BelongsTo;
 use Illuminate\Contracts\Filesystem\FileNotFoundException;
 
 /**
- * @property int $id
  * @property Account $account
- * @property int $account_id
  * @property User $user
- * @property int $user_id
- * @property bool $failed
- * @property string $failed_reason
- * @property string $filename
- * @property int $contacts_found
- * @property int $contacts_skipped
- * @property int $contacts_imported
- * @property \Carbon\Carbon $started_at
- * @property \Carbon\Carbon $ended_at
  */
 class ImportJob extends Model
 {
diff --git a/app/Models/Account/ImportJobReport.php b/app/Models/Account/ImportJobReport.php
index b4f465690..ec1948602 100644
--- a/app/Models/Account/ImportJobReport.php
+++ b/app/Models/Account/ImportJobReport.php
@@ -8,13 +8,7 @@ use Illuminate\Database\Eloquent\Relations\BelongsTo;
 
 /**
  * @property Account $account
- * @property int $account_id
  * @property User $user
- * @property int $user_id
- * @property int $import_job_id
- * @property string $contact_information
- * @property bool $skipped
- * @property string $skip_reason
  */
 class ImportJobReport extends Model
 {
diff --git a/app/Models/Account/Invitation.php b/app/Models/Account/Invitation.php
index 6a245e8b5..0c0ab9742 100644
--- a/app/Models/Account/Invitation.php
+++ b/app/Models/Account/Invitation.php
@@ -10,7 +10,6 @@ use Illuminate\Database\Eloquent\Relations\BelongsTo;
 /**
  * @property Account $account
  * @property User $invitedBy
- * @property string $invitation_key
  */
 class Invitation extends Model
 {
diff --git a/app/Models/Account/Photo.php b/app/Models/Account/Photo.php
index a1bff81fe..7aa043268 100644
--- a/app/Models/Account/Photo.php
+++ b/app/Models/Account/Photo.php
@@ -2,9 +2,9 @@
 
 namespace App\Models\Account;
 
-use App\Helpers\StorageHelper;
 use App\Models\Contact\Contact;
 use App\Models\ModelBinding as Model;
+use Illuminate\Support\Facades\Storage;
 use Illuminate\Database\Eloquent\Relations\BelongsTo;
 use Illuminate\Database\Eloquent\Relations\BelongsToMany;
 
@@ -63,6 +63,6 @@ class Photo extends Model
     {
         $url = $this->new_filename;
 
-        return asset(StorageHelper::disk(config('filesystems.default'))->url($url));
+        return asset(Storage::disk(config('filesystems.default'))->url($url));
     }
 }
diff --git a/app/Models/Contact/Contact.php b/app/Models/Contact/Contact.php
index 4d043b0bc..d5927fb80 100644
--- a/app/Models/Contact/Contact.php
+++ b/app/Models/Contact/Contact.php
@@ -3,13 +3,13 @@
 namespace App\Models\Contact;
 
 use Carbon\Carbon;
+use App\Models\User\User;
 use App\Traits\Searchable;
 use Illuminate\Support\Str;
 use App\Helpers\LocaleHelper;
 use App\Models\Account\Photo;
 use App\Models\Journal\Entry;
 use function Safe\preg_split;
-use App\Helpers\StorageHelper;
 use App\Helpers\WeatherHelper;
 use App\Models\Account\Account;
 use App\Models\Account\Weather;
@@ -955,7 +955,7 @@ class Contact extends Model
 
         try {
             $matches = preg_split('/\?/', $this->avatar_default_url);
-            $url = asset(StorageHelper::disk(config('filesystems.default'))->url($matches[0]));
+            $url = asset(Storage::disk(config('filesystems.default'))->url($matches[0]));
             if (count($matches) > 1) {
                 $url .= '?'.$matches[1];
             }
diff --git a/app/Models/Contact/Debt.php b/app/Models/Contact/Debt.php
index 2cdf1ac4f..8b6c44b3d 100644
--- a/app/Models/Contact/Debt.php
+++ b/app/Models/Contact/Debt.php
@@ -9,7 +9,6 @@ use App\Models\ModelBindingHasherWithContact as Model;
 /**
  * @property Account $account
  * @property Contact $contact
- * @property int $amount
  * @method static Builder due()
  * @method static Builder owed()
  * @method static Builder inProgress()
diff --git a/app/Models/Contact/Document.php b/app/Models/Contact/Document.php
index f17e5eaf6..397f3ee20 100644
--- a/app/Models/Contact/Document.php
+++ b/app/Models/Contact/Document.php
@@ -2,9 +2,9 @@
 
 namespace App\Models\Contact;
 
-use App\Helpers\StorageHelper;
 use App\Models\Account\Account;
 use App\Models\ModelBinding as Model;
+use Illuminate\Support\Facades\Storage;
 use Illuminate\Database\Eloquent\Relations\BelongsTo;
 
 class Document extends Model
@@ -61,6 +61,6 @@ class Document extends Model
     {
         $url = $this->new_filename;
 
-        return asset(StorageHelper::disk(config('filesystems.default'))->url($url));
+        return asset(Storage::disk(config('filesystems.default'))->url($url));
     }
 }
diff --git a/app/Models/Contact/Gift.php b/app/Models/Contact/Gift.php
index c0ba490e1..7d53294d4 100644
--- a/app/Models/Contact/Gift.php
+++ b/app/Models/Contact/Gift.php
@@ -15,11 +15,6 @@ use Illuminate\Database\Eloquent\Relations\BelongsToMany;
  * @property Account $account
  * @property Contact $contact
  * @property Contact $recipient
- * @property string $name
- * @property string $comment
- * @property string $url
- * @property Contact $is_for
- * @property int $value
  * @method static Builder offered()
  * @method static Builder isIdea()
  */
diff --git a/app/Models/Contact/Note.php b/app/Models/Contact/Note.php
index 5d7be880c..6af42d8c0 100644
--- a/app/Models/Contact/Note.php
+++ b/app/Models/Contact/Note.php
@@ -13,10 +13,6 @@ use Illuminate\Database\Eloquent\Relations\BelongsTo;
  * @property Account $account
  * @property Contact $contact
  * @property string $parsed_body
- * @property string $body
- * @property bool $is_favorited
- * @property \Carbon\Carbon $favorited_at
- * @property \Carbon\Carbon $created_at
  */
 class Note extends Model
 {
diff --git a/app/Models/Contact/ReminderOutbox.php b/app/Models/Contact/ReminderOutbox.php
index bea78c6ec..e6210bec7 100644
--- a/app/Models/Contact/ReminderOutbox.php
+++ b/app/Models/Contact/ReminderOutbox.php
@@ -11,15 +11,7 @@ use App\Models\ModelBindingHasherWithContact as Model;
 
 /**
  * @property Account $account
- * @property int $account_id
  * @property Contact $contact
- * @property User $user
- * @property int $user_id
- * @property Reminder $reminder
- * @property int $reminder_id
- * @property string $nature
- * @property \Carbon\Carbon $planned_date
- * @property int $notification_number_days_before
  */
 class ReminderOutbox extends Model
 {
diff --git a/app/Models/Contact/ReminderSent.php b/app/Models/Contact/ReminderSent.php
index 883e13e72..a5a1c8e53 100644
--- a/app/Models/Contact/ReminderSent.php
+++ b/app/Models/Contact/ReminderSent.php
@@ -9,17 +9,7 @@ use App\Models\ModelBindingHasherWithContact as Model;
 
 /**
  * @property Account $account
- * @property int $account_id
  * @property Contact $contact
- * @property User $user
- * @property int $user_id
- * @property int $reminder_id
- * @property string $html_content
- * @property int $frequency_number
- * @property string $frequency_type
- * @property string $nature
- * @property \Carbon\Carbon $sent_date
- * @property \Carbon\Carbon $planned_date
  */
 class ReminderSent extends Model
 {
diff --git a/app/Models/Contact/Task.php b/app/Models/Contact/Task.php
index 9bb4c7f0f..87e338b6b 100644
--- a/app/Models/Contact/Task.php
+++ b/app/Models/Contact/Task.php
@@ -10,14 +10,6 @@ use Illuminate\Database\Eloquent\Relations\BelongsTo;
 /**
  * @property Account $account
  * @property Contact $contact
- * @property int $id
- * @property string $title
- * @property string $description
- * @property string $uuid
- * @property bool $completed
- * @property \Carbon\Carbon $completed_at
- * @property \Carbon\Carbon $created_at
- * @property \Carbon\Carbon $updated_at
  * @method static Builder completed()
  * @method static Builder inProgress()
  */
diff --git a/app/Models/Journal/JournalEntry.php b/app/Models/Journal/JournalEntry.php
index 0abd9edca..eebe21955 100644
--- a/app/Models/Journal/JournalEntry.php
+++ b/app/Models/Journal/JournalEntry.php
@@ -10,14 +10,8 @@ use Illuminate\Database\Eloquent\Relations\MorphTo;
 use Illuminate\Database\Eloquent\Relations\BelongsTo;
 
 /**
- * @property int $id
  * @property Account $account
  * @property User $invitedBy
- * @property int $account_id
- * @property IsJournalableInterface $journalable
- * @property int $journalable_id
- * @property string $journalable_type
- * @property \Carbon\Carbon $date
  */
 class JournalEntry extends Model
 {
diff --git a/app/Providers/AppServiceProvider.php b/app/Providers/AppServiceProvider.php
index 4af74dae3..d3ec9bbd6 100644
--- a/app/Providers/AppServiceProvider.php
+++ b/app/Providers/AppServiceProvider.php
@@ -35,7 +35,7 @@ class AppServiceProvider extends ServiceProvider
         );
 
         if (config('database.use_utf8mb4')
-            && DBHelper::connection()->getDriverName() == 'mysql'
+            && DB::connection()->getDriverName() == 'mysql'
             && ! DBHelper::testVersion('5.7.7')) {
             Schema::defaultStringLength(191);
         }
diff --git a/app/Services/Contact/Avatar/UpdateAvatar.php b/app/Services/Contact/Avatar/UpdateAvatar.php
index b615a83d2..9bbe72cd4 100644
--- a/app/Services/Contact/Avatar/UpdateAvatar.php
+++ b/app/Services/Contact/Avatar/UpdateAvatar.php
@@ -57,8 +57,8 @@ class UpdateAvatar extends BaseService
         switch ($contact->avatar_source) {
             case 'photo':
             // in case of a photo, set the photo as the avatar
-                $contact->avatar_photo_id = $this->nullOrValue($data, 'photo_id');
-                $contact->photos()->syncWithoutDetaching([$this->nullOrValue($data, 'photo_id')]);
+                $contact->avatar_photo_id = $data['photo_id'];
+                $contact->photos()->syncWithoutDetaching([$data['photo_id']]);
                 break;
             default:
                 $contact->avatar_photo_id = null;
diff --git a/database/migrations/2017_06_07_173437_add_multiple_genders_choices.php b/database/migrations/2017_06_07_173437_add_multiple_genders_choices.php
index 3ba9fcc8f..5fc7d2c59 100644
--- a/database/migrations/2017_06_07_173437_add_multiple_genders_choices.php
+++ b/database/migrations/2017_06_07_173437_add_multiple_genders_choices.php
@@ -13,7 +13,7 @@ class AddMultipleGendersChoices extends Migration
      */
     public function up()
     {
-        $driverName = DBHelper::connection()->getDriverName();
+        $driverName = DB::connection()->getDriverName();
         switch ($driverName) {
             case 'mysql':
                 DB::statement('ALTER TABLE '.DBHelper::getTable('contacts')." CHANGE COLUMN gender gender ENUM('male', 'female', 'none')");
diff --git a/database/migrations/2017_10_14_083556_change_gift_column_structure.php b/database/migrations/2017_10_14_083556_change_gift_column_structure.php
index c014f9b19..4112184fb 100644
--- a/database/migrations/2017_10_14_083556_change_gift_column_structure.php
+++ b/database/migrations/2017_10_14_083556_change_gift_column_structure.php
@@ -1,6 +1,5 @@
 <?php
 
-use App\Helpers\DBHelper;
 use Illuminate\Support\Facades\DB;
 use Illuminate\Support\Facades\Schema;
 use Illuminate\Database\Schema\Blueprint;
@@ -16,7 +15,7 @@ class ChangeGiftColumnStructure extends Migration
     public function up()
     {
         Schema::table('gifts', function (Blueprint $table) {
-            if (DBHelper::connection()->getDriverName() == 'pgsql') {
+            if (DB::connection()->getDriverName() == 'pgsql') {
                 //Postgresql does not implicitly convert varchar's to integers, therefore add USING ...
                 DB::statement('ALTER TABLE gifts ALTER about_object_id TYPE INT USING about_object_id::integer');
             } else {
diff --git a/database/migrations/2018_10_07_120133_fix_json_column.php b/database/migrations/2018_10_07_120133_fix_json_column.php
index 3103d7e5c..524773ef4 100644
--- a/database/migrations/2018_10_07_120133_fix_json_column.php
+++ b/database/migrations/2018_10_07_120133_fix_json_column.php
@@ -13,7 +13,7 @@ class FixJsonColumn extends Migration
      */
     public function up()
     {
-        $connection = DBHelper::connection();
+        $connection = DB::connection();
 
         if ($connection->getDriverName() != 'mysql') {
             return;
diff --git a/psalm.xml b/psalm.xml
index f45487ea4..f210b406c 100644
--- a/psalm.xml
+++ b/psalm.xml
@@ -114,17 +114,5 @@
             </errorLevel>
         </UndefinedPropertyFetch>
 
-        <UndefinedMagicMethod>
-            <errorLevel type="suppress">
-                <file name="app/Traits/Searchable.php" />
-            </errorLevel>
-        </UndefinedMagicMethod>
-
-        <InvalidStaticInvocation>
-            <errorLevel type="suppress">
-                <file name="app/Providers/RouteServiceProvider.php" />
-            </errorLevel>
-        </InvalidStaticInvocation>
-
     </issueHandlers>
 </psalm>
