<?php

namespace App\Console\Commands;

use App\Models\CouchUser;
use App\Helpers\CouchDbHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class MigrateCouch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:couchdb';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate Mysql to CouchDB';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        // if (! $this->confirm('Are you sure you want to proceed? This will delete ALL couchdb data in your environment. YOU SHOULD MAKE A BACKUP BEFORE RUNNING THIS COMMAND.')) {
        //     return;
        // }

        $clientUsers = CouchDbHelper::client('_users');
        $adm = CouchDbHelper::admin();

        $this->info('✓ Removing every couchdb database');
        $dbs = $clientUsers->listDatabases();
        foreach ($dbs as $dbName) {
            $db = CouchDbHelper::client($dbName);
            $db->deleteDatabase();
        }
        $this->info('✓ Creating _users database');
        $clientUsers->createDatabase();

        /**
         * Users.
         */
        $users = DB::table('users')->get();
        $this->info('✓ Migrating '.count($users).' users');
        $output = new ConsoleOutput();
        $progress = new ProgressBar($output, count($users));
        $progress->start();
        foreach ($users as $user) {
            CouchUser::createFromUser($user);
            $progress->advance();
        }
        $progress->finish();
        $this->info('');
        $this->info('');

        /**
         * Accounts.
         */
        $accounts = DB::table('accounts')->get();
        $this->info('✓ Migrating '.count($accounts).' accounts');
        $output = new ConsoleOutput();
        $progress = new ProgressBar($output, count($accounts));
        $progress->start();
        foreach ($accounts as $account) {
            // create its database
            $client = CouchDbHelper::getAccountDatabase($account->id);
            $client->createDatabase();
            CouchDbHelper::admin(CouchDbHelper::getAccountDatabaseName($account->id))->addDatabaseMemberRole(CouchDbHelper::getAccountDatabaseRoleName($account->id));

            //setting note design documents
            $favNotesFn = "function(doc) { if (doc.type === 'note' && doc.is_favorited === true) { emit(doc.created_at, null); } }";
            $notesByContact = "function(doc) { if (doc.type === 'note') { emit([doc.contact_id, doc.created_at], null); } }";
            $design_doc = (object) [
                '_id' => '_design/notes',
                'language' => 'javascript',
                'views' => [
                    'favorites'=> ['map' => $favNotesFn],
                    'byContact' => ['map' => $notesByContact],
                ],
            ];
            $client->storeDoc($design_doc);
            $progress->advance();
        }
        $progress->finish();
        $this->info('');
        $this->info('');

        $notes = DB::table('notes')->get();
        $this->info('✓ Migrating '.count($notes).' notes');
        $output = new ConsoleOutput();
        $progress = new ProgressBar($output, count($notes));
        $progress->start();
        foreach ($notes as $note) {
            $client = CouchDbHelper::getAccountDatabase($note->account_id);

            $newDoc = (object) [
                '_id' => 'note-legacy-'.$note->id,
                'type' => 'note',
                'contact_id' => $note->contact_id,
                'created_at' => $note->created_at,
                'updated_at' => $note->updated_at,
                'body' => $note->body,
                'is_favorited' => $note->is_favorited ? true : false,
                'favorited_at' => $note->favorited_at,
            ];
            // Use of CouchNote model
            $client->storeDoc($newDoc);
            $progress->advance();
        }

        $progress->finish();
        $this->info('');
        $this->info('');

        $this->info('✓ Done migrating CouchDB. Enjoy your brand new offline first database !');
        $this->info('');
    }
}
