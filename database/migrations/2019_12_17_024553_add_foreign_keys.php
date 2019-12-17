<?php

use App\Models\Account\Account;
use App\Models\Account\ActivityStatistic;
use App\Models\Account\ImportJob;
use App\Models\Account\ImportJobReport;
use App\Models\Account\Invitation;
use App\Models\Contact\Call;
use App\Models\Contact\Contact;
use App\Models\Contact\Debt;
use App\Models\Contact\Gender;
use App\Models\Contact\Gift;
use App\Models\Contact\Tag;
use App\Models\Journal\Day;
use App\Models\Journal\Entry;
use App\Models\Journal\JournalEntry;
use App\Models\User\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
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
    }

    private function cleanActivityStatisticTable()
    {
        foreach (ActivityStatistic::cursor() as $activityStat) {
            try {
                Account::findOrFail($activityStat->account_id);
                Contact::findOrFail($activityStat->contact_id);
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
                Account::findOrFail($call->account_id);
                Contact::findOrFail($call->contact_id);
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
        $contactTags = DB::table('contact_tag')->get();
        foreach ($contactTags->cursor() as $contactTag) {
            try {
                Account::findOrFail($contactTag->account_id);
                Contact::findOrFail($contactTag->contact_id);
                Tag::findOrFail($contactTag->tag_id);
            } catch (ModelNotFoundException $e) {
                $contactTag->delete();
                continue;
            }
        }

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
                Account::findOrFail($day->account_id);
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
                Account::findOrFail($debt->account_id);
                Contact::findOrFail($debt->contact_id);
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
                Account::findOrFail($entry->account_id);
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
                Account::findOrFail($gender->account_id);
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
                Account::findOrFail($gift->account_id);
                Contact::findOrFail($gift->contact_id);
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
                Account::findOrFail($importJobReport->account_id);
                User::findOrFail($importJobReport->user_id);
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
                Account::findOrFail($importJob->account_id);
                User::findOrFail($importJob->user_id);
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
                Account::findOrFail($invitation->account_id);
                User::findOrFail($invitation->invited_by_user_id);
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
                Account::findOrFail($journalEntry->account_id);
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
}
