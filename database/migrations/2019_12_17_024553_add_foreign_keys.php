<?php

use App\Models\User\User;
use App\Models\Contact\Pet;
use App\Models\Contact\Tag;
use App\Models\Journal\Day;
use App\Models\User\Module;
use App\Models\Contact\Call;
use App\Models\Contact\Debt;
use App\Models\Contact\Gift;
use App\Models\Contact\Note;
use App\Models\Contact\Task;
use App\Models\Journal\Entry;
use App\Models\Settings\Term;
use App\Models\Contact\Gender;
use App\Models\Contact\Contact;
use App\Models\Account\ImportJob;
use App\Models\Account\Invitation;
use Illuminate\Support\Facades\DB;
use App\Models\Contact\PetCategory;
use App\Models\Instance\SpecialDate;
use App\Models\Journal\JournalEntry;
use Illuminate\Support\Facades\Schema;
use App\Models\Account\ImportJobReport;
use App\Models\Account\ActivityStatistic;
use App\Models\Relationship\Relationship;
use Illuminate\Database\Schema\Blueprint;
use App\Models\Relationship\RelationshipType;
use Illuminate\Database\Migrations\Migration;
use App\Models\Relationship\RelationshipTypeGroup;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeys extends Migration
{
    private $existingAccounts;
    private $existingUsers;
    private $existingContacts;

    /**
     * Add foreign keys to all the tables that don't have ones.
     * Before adding foreign keys, in all the tables, we check whether the
     * foreign keys actually have data from the table they point to. This will
     * ensure data integrity from now on.
     * In order to do this, we need to parse a lot of contacts, accounts and
     * users, as most data point to them somehow. For those 3 models, when we
     * launch the script, we put all those ids in arrays. Then, for each
     * foreign key, we check in the arrays if the key exists, instead of querying
     * the database again and again. This is much faster.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        $this->initialize();
        $this->cleanActivityStatisticTable();
        $this->cleanCallsTable();
        $this->cleanContactTagTable();
        $this->cleanDayTable();
        $this->cleanDebtTable();
        $this->cleanEntriesTable();
        $this->cleanGenderTable();
        $this->cleanGiftTable();
        $this->cleanImportJobReportTable();
        $this->cleanImportJobTable();
        $this->cleanInvitationTable();
        $this->cleanJournalEntryTable();
        $this->cleanModuleTable();
        $this->cleanNoteTable();
        $this->cleanNotificationTable();
        $this->cleanPetTable();
        $this->cleanRelationshipTypeGroupTable();
        $this->cleanRelationshipTypeTable();
        $this->cleanRelationshipTable();
        $this->cleanSpecialDateTable();
        $this->cleanTagTable();
        $this->cleanTaskTable();
        $this->cleanTermUserTable();
        $this->cleanUserTable();
        $this->cleanContactTable();

        Schema::enableForeignKeyConstraints();
    }

    private function initialize()
    {
        $rows = DB::table('contacts')->select('id')->get();
        $this->existingContacts = [];
        foreach ($rows as $row) {
            $this->existingContacts[$row->id] = 1;
        }

        $rows = DB::table('users')->select('id')->get();
        $this->existingUsers = [];
        foreach ($rows as $row) {
            $this->existingUsers[$row->id] = 1;
        }

        $rows = DB::table('accounts')->select('id')->get();
        $this->existingAccounts = [];
        foreach ($rows as $row) {
            $this->existingAccounts[$row->id] = 1;
        }
    }

    private function contactExistOrFail(int $id)
    {
        if (isset($this->existingContacts[$id])) {
            return true;
        } else {
            throw new ModelNotFoundException();
        }
    }

    private function userExistOrFail(int $id)
    {
        if (isset($this->existingUsers[$id])) {
            return true;
        } else {
            throw new ModelNotFoundException();
        }
    }

    private function accountExistOrFail(int $id)
    {
        if (isset($this->existingAccounts[$id])) {
            return true;
        } else {
            throw new ModelNotFoundException();
        }
    }

    private function cleanActivityStatisticTable()
    {
        foreach (ActivityStatistic::cursor() as $activityStat) {
            try {
                $this->accountExistOrFail($activityStat->account_id);
                $this->contactExistOrFail($activityStat->contact_id);
            } catch (ModelNotFoundException $e) {
                $activityStat->delete();
                continue;
            }
        }

        Schema::table('activity_statistics', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }

    private function cleanCallsTable()
    {
        foreach (Call::cursor() as $call) {
            try {
                $this->accountExistOrFail($call->account_id);
                $this->contactExistOrFail($call->contact_id);
            } catch (ModelNotFoundException $e) {
                $call->delete();
                continue;
            }
        }

        Schema::table('calls', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }

    private function cleanContactTagTable()
    {
        DB::table('contact_tag')
            ->orderBy('contact_id')
            ->chunk(200, function ($contactTags) {
                foreach ($contactTags as $contactTag) {
                    try {
                        $this->accountExistOrFail($contactTag->account_id);
                        $this->accountExistOrFail($contactTag->contact_id);
                        Tag::findOrFail($contactTag->tag_id);
                    } catch (ModelNotFoundException $e) {
                        DB::table('contact_tag')
                            ->where('account_id', $contactTag->account_id)
                            ->where('contact_id', $contactTag->contact_id)
                            ->where('tag_id', $contactTag->tag_id)
                            ->delete();
                    }
                }
            });

        Schema::table('contact_tag', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->unsignedInteger('tag_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
        });
    }

    private function cleanDayTable()
    {
        foreach (Day::cursor() as $day) {
            try {
                $this->accountExistOrFail($day->account_id);
            } catch (ModelNotFoundException $e) {
                $day->delete();
                continue;
            }
        }

        Schema::table('days', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }

    private function cleanDebtTable()
    {
        foreach (Debt::cursor() as $debt) {
            try {
                $this->accountExistOrFail($debt->account_id);
                $this->contactExistOrFail($debt->contact_id);
            } catch (ModelNotFoundException $e) {
                $debt->delete();
                continue;
            }
        }

        Schema::table('debts', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }

    private function cleanEntriesTable()
    {
        foreach (Entry::cursor() as $entry) {
            try {
                $this->accountExistOrFail($entry->account_id);
            } catch (ModelNotFoundException $e) {
                $entry->delete();
                continue;
            }
        }

        Schema::table('entries', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }

    private function cleanGenderTable()
    {
        foreach (Gender::cursor() as $gender) {
            try {
                $this->accountExistOrFail($gender->account_id);
            } catch (ModelNotFoundException $e) {
                $gender->delete();
                continue;
            }
        }

        Schema::table('genders', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }

    private function cleanGiftTable()
    {
        foreach (Gift::cursor() as $gift) {
            try {
                $this->accountExistOrFail($gift->account_id);
                $this->contactExistOrFail($gift->contact_id);
            } catch (ModelNotFoundException $e) {
                $gift->delete();
                continue;
            }
        }

        Schema::table('gifts', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }

    private function cleanImportJobReportTable()
    {
        foreach (ImportJobReport::cursor() as $importJobReport) {
            try {
                $this->accountExistOrFail($importJobReport->account_id);
                $this->userExistOrFail($importJobReport->user_id);
                ImportJob::findOrFail($importJobReport->import_job_id);
            } catch (ModelNotFoundException $e) {
                $importJobReport->delete();
                continue;
            }
        }

        Schema::table('import_job_reports', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('user_id')->change();
            $table->unsignedInteger('import_job_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('import_job_id')->references('id')->on('import_jobs')->onDelete('cascade');
        });
    }

    private function cleanImportJobTable()
    {
        foreach (ImportJob::cursor() as $importJob) {
            try {
                $this->accountExistOrFail($importJob->account_id);
                $this->userExistOrFail($importJob->user_id);
            } catch (ModelNotFoundException $e) {
                $importJob->delete();
                continue;
            }
        }

        Schema::table('import_jobs', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('user_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    private function cleanInvitationTable()
    {
        foreach (Invitation::cursor() as $invitation) {
            try {
                $this->accountExistOrFail($invitation->account_id);
                $this->userExistOrFail($invitation->invited_by_user_id);
            } catch (ModelNotFoundException $e) {
                $invitation->delete();
                continue;
            }
        }

        Schema::table('invitations', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('invited_by_user_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('invited_by_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    private function cleanJournalEntryTable()
    {
        foreach (JournalEntry::cursor() as $journalEntry) {
            try {
                $this->accountExistOrFail($journalEntry->account_id);
            } catch (ModelNotFoundException $e) {
                $journalEntry->delete();
                continue;
            }
        }

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }

    private function cleanModuleTable()
    {
        foreach (Module::cursor() as $module) {
            try {
                $this->accountExistOrFail($module->account_id);
            } catch (ModelNotFoundException $e) {
                $module->delete();
                continue;
            }
        }

        Schema::table('modules', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }

    private function cleanNoteTable()
    {
        foreach (Note::cursor() as $note) {
            try {
                $this->accountExistOrFail($note->account_id);
                $this->contactExistOrFail($note->contact_id);
            } catch (ModelNotFoundException $e) {
                $note->delete();
                continue;
            }
        }

        Schema::table('notes', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }

    private function cleanNotificationTable()
    {
        // this table is not used anymore, we can safely remove it from the
        // database
        Schema::drop('notifications');
    }

    private function cleanPetTable()
    {
        foreach (Pet::cursor() as $pet) {
            try {
                $this->accountExistOrFail($pet->account_id);
                $this->contactExistOrFail($pet->contact_id);
                PetCategory::findOrFail($pet->pet_category_id);
            } catch (ModelNotFoundException $e) {
                $pet->delete();
                continue;
            }
        }

        Schema::table('pets', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->unsignedInteger('pet_category_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('pet_category_id')->references('id')->on('pet_categories')->onDelete('cascade');
        });
    }

    private function cleanRelationshipTypeGroupTable()
    {
        foreach (RelationshipTypeGroup::cursor() as $type) {
            try {
                $this->accountExistOrFail($type->account_id);
            } catch (ModelNotFoundException $e) {
                $type->delete();
                continue;
            }
        }

        Schema::table('relationship_type_groups', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }

    private function cleanRelationshipTypeTable()
    {
        foreach (RelationshipType::cursor() as $type) {
            try {
                $this->accountExistOrFail($type->account_id);
                RelationshipTypeGroup::findOrFail($type->relationship_type_group_id);
            } catch (ModelNotFoundException $e) {
                $type->delete();
                continue;
            }
        }

        Schema::table('relationship_types', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('relationship_type_group_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('relationship_type_group_id')->references('id')->on('relationship_type_groups')->onDelete('cascade');
        });
    }

    private function cleanRelationshipTable()
    {
        foreach (Relationship::cursor() as $relationship) {
            try {
                $this->accountExistOrFail($relationship->account_id);
                RelationshipType::findOrFail($relationship->relationship_type_id);
                $this->contactExistOrFail($relationship->contact_is);
                $this->contactExistOrFail($relationship->of_contact);
            } catch (ModelNotFoundException $e) {
                $relationship->delete();
                continue;
            }
        }

        Schema::table('relationships', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('relationship_type_id')->change();
            $table->unsignedInteger('contact_is')->change();
            $table->unsignedInteger('of_contact')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('relationship_type_id')->references('id')->on('relationship_types')->onDelete('cascade');
            $table->foreign('contact_is')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('of_contact')->references('id')->on('contacts')->onDelete('cascade');
        });
    }

    private function cleanSpecialDateTable()
    {
        foreach (SpecialDate::cursor() as $date) {
            try {
                $this->accountExistOrFail($date->account_id);
                $this->contactExistOrFail($date->contact_id);
            } catch (ModelNotFoundException $e) {
                $date->delete();
                continue;
            }
        }

        Schema::table('special_dates', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }

    private function cleanTagTable()
    {
        foreach (Tag::cursor() as $tag) {
            try {
                $this->accountExistOrFail($tag->account_id);
            } catch (ModelNotFoundException $e) {
                $tag->delete();
                continue;
            }
        }

        Schema::table('tags', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }

    private function cleanTaskTable()
    {
        foreach (Task::cursor() as $task) {
            try {
                $this->accountExistOrFail($task->account_id);

                if (! is_null($task->contact_id)) {
                    $this->contactExistOrFail($task->contact_id);
                }
            } catch (ModelNotFoundException $e) {
                $task->delete();
                continue;
            }
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }

    private function cleanTermUserTable()
    {
        DB::table('term_user')
            ->orderBy('user_id')
            ->chunk(200, function ($termUsers) {
                foreach ($termUsers as $termUser) {
                    try {
                        $this->accountExistOrFail($termUser->account_id);
                        $this->userExistOrFail($termUser->user_id);
                        Term::findOrFail($termUser->term_id);
                    } catch (ModelNotFoundException $e) {
                        DB::table('term_user')
                            ->where('account_id', $termUser->account_id)
                            ->where('user_id', $termUser->user_id)
                            ->where('term_id', $termUser->term_id)
                            ->delete();
                    }
                }
            });

        Schema::table('term_user', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('user_id')->change();
            $table->unsignedInteger('term_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('term_id')->references('id')->on('terms')->onDelete('cascade');
        });
    }

    private function cleanUserTable()
    {
        foreach (User::cursor() as $user) {
            try {
                $this->accountExistOrFail($user->account_id);
            } catch (ModelNotFoundException $e) {
                $user->delete();
                continue;
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('currency_id')->nullable()->change();
            $table->unsignedInteger('invited_by_user_id')->nullable()->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('set null');
            $table->foreign('invited_by_user_id')->references('id')->on('users')->onDelete('set null');
        });

        foreach (User::cursor() as $user) {
            try {
                if (! is_null($user->invited_by_user_id)) {
                    $this->userExistOrFail($user->invited_by_user_id);
                }
            } catch (ModelNotFoundException $e) {
                $user->invited_by_user_id = null;
                $user->save();
                continue;
            }
        }
    }

    private function cleanContactTable()
    {
        foreach (Contact::cursor() as $contact) {
            try {
                $this->accountExistOrFail($contact->account_id);
            } catch (ModelNotFoundException $e) {
                $contact->delete();
                continue;
            }
        }

        Schema::table('contacts', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('avatar_photo_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('avatar_photo_id')->references('id')->on('photos')->onDelete('set null');
        });
    }
}
