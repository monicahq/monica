<?php

namespace App\Console\Commands;

use App\Contact\ManageContact\Services\CreateContact;
use App\Contact\ManageContactImportantDates\Services\CreateContactImportantDate;
use App\Contact\ManageGoals\Services\CreateGoal;
use App\Contact\ManageGoals\Services\ToggleStreak;
use App\Contact\ManageNotes\Services\CreateNote;
use App\Contact\ManageTasks\Services\CreateContactTask;
use App\Exceptions\EntryAlreadyExistException;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use App\Models\Group;
use App\Models\Note;
use App\Models\User;
use App\Models\Vault;
use App\Settings\CreateAccount\Services\CreateAccount;
use App\Vault\ManageJournals\Services\CreateJournal;
use App\Vault\ManageJournals\Services\CreatePost;
use App\Vault\ManageVault\Services\CreateVault;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

/**
 * @codeCoverageIgnore
 */
class SetupDummyAccount extends Command
{
    use ConfirmableTrait;

    protected ?\Faker\Generator $faker;

    protected User $firstUser;

    protected User $secondUser;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:dummy
                            {--force : Force the operation to run.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prepare an account with fake data so users can play with it';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->start();
        $this->wipeAndMigrateDB();
        $this->createFirstUsers();
        $this->createVaults();
        $this->createContacts();
        $this->createNotes();
        $this->createTasks();
        $this->createGoals();
        $this->createJournals();
        $this->stop();
    }

    private function start(): void
    {
        if (! $this->confirmToProceed('Are you sure you want to proceed? This will delete ALL data in your environment.', true)) {
            exit;
        }

        $this->line('This process will take a few minutes to complete. Be patient and read a book in the meantime.');
        $this->faker = Faker::create();
    }

    private function wipeAndMigrateDB(): void
    {
        $this->artisan('☐ Flush search engine', 'scout:flush', ['model' => Note::class]);
        $this->artisan('☐ Flush search engine', 'scout:flush', ['model' => Contact::class]);
        $this->artisan('☐ Flush search engine', 'scout:flush', ['model' => Group::class]);

        $this->artisan('☐ Reset search engine', 'monica:setup');
        $this->artisan('☐ Migration of the database', 'migrate:fresh');
        $this->artisan('☐ Symlink the storage folder', 'storage:link');
    }

    private function stop(): void
    {
        $this->line('');
        $this->line('-----------------------------');
        $this->line('|');
        $this->line('| Welcome to Monica');
        $this->line('|');
        $this->line('-----------------------------');
        $this->info('| You can now sign in with one of these two accounts:');
        $this->line('| An account with a lot of data:');
        $this->line('| username: admin@admin.com');
        $this->line('| password: admin123');
        $this->line('|------------------------–––-');
        $this->line('|A blank account:');
        $this->line('| username: blank@blank.com');
        $this->line('| password: blank123');
        $this->line('|------------------------–––-');
        $this->line('| URL:      '.config('app.url'));
        $this->line('-----------------------------');

        $this->info('Setup is done. Have fun.');
    }

    private function createFirstUsers(): void
    {
        $this->info('☐ Create first user of the account');

        $this->firstUser = (new CreateAccount())->execute([
            'email' => 'admin@admin.com',
            'password' => 'admin123',
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);
        $this->firstUser->email_verified_at = Carbon::now();
        $this->firstUser->save();
    }

    private function createVaults(): void
    {
        $this->info('☐ Create vaults');

        for ($i = 0; $i < rand(3, 5); $i++) {
            (new CreateVault())->execute([
                'account_id' => $this->firstUser->account_id,
                'author_id' => $this->firstUser->id,
                'type' => Vault::TYPE_PERSONAL,
                'name' => $this->faker->firstName,
                'description' => rand(1, 2) == 1 ? $this->faker->sentence() : null,
            ]);
        }
    }

    private function createContacts(): void
    {
        $this->info('☐ Create contacts');

        foreach (Vault::all() as $vault) {
            for ($i = 0; $i < rand(2, 13); $i++) {
                $date = $this->faker->dateTimeThisCentury();
                $birthDate = Carbon::parse($date);

                $contact = (new CreateContact())->execute([
                    'account_id' => $this->firstUser->account_id,
                    'author_id' => $this->firstUser->id,
                    'vault_id' => $vault->id,
                    'first_name' => $this->faker->firstName(),
                    'last_name' => $this->faker->lastName(),
                    'middle_name' => rand(1, 2) == 1 ? $this->faker->lastName() : null,
                    'nickname' => null,
                    'maiden_name' => null,
                    'listed' => true,
                ]);

                (new CreateContactImportantDate())->execute([
                    'account_id' => $this->firstUser->account_id,
                    'author_id' => $this->firstUser->id,
                    'vault_id' => $vault->id,
                    'contact_id' => $contact->id,
                    'label' => 'Birthdate',
                    'day' => $birthDate->day,
                    'month' => $birthDate->month,
                    'year' => rand(1, 2) == 1 ? $birthDate->year : null,
                    'type' => ContactImportantDate::TYPE_BIRTHDATE,
                ]);
            }
        }
    }

    private function createNotes(): void
    {
        $this->info('☐ Create notes');

        foreach (Contact::all() as $contact) {
            for ($i = 0; $i < 4; $i++) {
                (new CreateNote())->execute([
                    'account_id' => $this->firstUser->account_id,
                    'author_id' => $this->firstUser->id,
                    'vault_id' => $contact->vault_id,
                    'contact_id' => $contact->id,
                    'title' => rand(1, 2) == 1 ? $this->faker->sentence(rand(3, 6)) : null,
                    'body' => $this->faker->paragraph(),
                ]);
            }
        }
    }

    private function createTasks(): void
    {
        $this->info('☐ Create tasks');

        foreach (Contact::all() as $contact) {
            for ($i = 0; $i < 4; $i++) {
                (new CreateContactTask())->execute([
                    'account_id' => $this->firstUser->account_id,
                    'author_id' => $this->firstUser->id,
                    'vault_id' => $contact->vault_id,
                    'contact_id' => $contact->id,
                    'label' => $this->faker->sentence(rand(3, 6)),
                    'description' => null,
                ]);
            }
        }
    }

    private function createGoals(): void
    {
        $this->info('☐ Create goals');

        $goals = collect([
            'Lose 5 kgs',
            'Practice sport every day',
            'Develop Monica every day',
            'Kiss my wife',
        ]);

        foreach (Contact::all() as $contact) {
            foreach ($goals->take(rand(1, 4)) as $goal) {
                $goal = (new CreateGoal())->execute([
                    'account_id' => $this->firstUser->account_id,
                    'author_id' => $this->firstUser->id,
                    'vault_id' => $contact->vault_id,
                    'contact_id' => $contact->id,
                    'name' => $goal,
                ]);

                for ($i = 0; $i < 4; $i++) {
                    $date = Carbon::now()->subYears(2);
                    for ($j = 0; $j < rand(1, 20); $j++) {
                        $date = $date->addDays(rand(1, 3));

                        try {
                            (new ToggleStreak())->execute([
                                'account_id' => $this->firstUser->account_id,
                                'author_id' => $this->firstUser->id,
                                'vault_id' => $contact->vault_id,
                                'contact_id' => $contact->id,
                                'goal_id' => $goal->id,
                                'happened_at' => $date->format('Y-m-d'),
                            ]);
                        } catch (EntryAlreadyExistException) {
                            continue;
                        }
                    }
                }
            }
        }
    }

    private function createJournals(): void
    {
        $this->info('☐ Create journals');

        $journals = collect([
            'Road trip',
            'My private diary',
            'Journal of 2022',
            'Incredible stories',
        ]);

        foreach (Vault::all() as $vault) {
            foreach ($journals->take(rand(1, 4)) as $journal) {
                $journal = (new CreateJournal())->execute([
                    'account_id' => $this->firstUser->account_id,
                    'author_id' => $this->firstUser->id,
                    'vault_id' => $vault->id,
                    'name' => $journal,
                    'description' => rand(1, 2) == 1 ? $this->faker->sentence() : null,
                ]);

                for ($j = 0; $j < rand(1, 20); $j++) {
                    (new CreatePost())->execute([
                        'account_id' => $this->firstUser->account_id,
                        'author_id' => $this->firstUser->id,
                        'vault_id' => $vault->id,
                        'journal_id' => $journal->id,
                        'title' => $this->faker->sentence(),
                        'content' => $this->faker->paragraphs(rand(1, 30), true),
                        'written_at' => $this->faker->dateTimeThisYear()->format('Y-m-d'),
                    ]);
                }
            }
        }
    }

    private function artisan(string $message, string $command, array $arguments = []): void
    {
        $this->info($message);
        $this->callSilent($command, $arguments);
    }
}
