<?php

namespace App\Domains\Contact\ManageContact\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Address;
use App\Models\Call;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\ContactInformation;
use App\Models\ContactImportantDate;
use App\Models\ContactReminder;
use App\Models\ContactTask;
use App\Models\File;
use App\Models\Goal;
use App\Models\Label;
use App\Models\MoodTrackingEvent;
use App\Models\Note;
use App\Models\Pet;
use App\Models\QuickFact;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class MergeContacts extends BaseService implements ServiceInterface
{
    private array $data;

    private Contact $primaryContact;

    private Contact $duplicateContact;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'primary_contact_id' => 'required|uuid|exists:contacts,id',
            'duplicate_contact_id' => 'required|uuid|exists:contacts,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Merge two contacts.
     */
    public function execute(array $data): Contact
    {
        $this->data = $data;
        $this->validate();
        $this->merge();
        $this->updateLastEditedDate();

        return $this->primaryContact;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->primaryContact = $this->vault->contacts()
            ->findOrFail($this->data['primary_contact_id']);

        $this->duplicateContact = $this->vault->contacts()
            ->findOrFail($this->data['duplicate_contact_id']);

        if ($this->primaryContact->id === $this->duplicateContact->id) {
            throw new ModelNotFoundException;
        }

        if ($this->primaryContact->vault_id !== $this->duplicateContact->vault_id) {
            throw new ModelNotFoundException;
        }
    }

    private function merge(): void
    {
        DB::transaction(function () {
            $this->mergeContactInformation();
            $this->mergeAddresses();
            $this->mergeLabels();
            $this->mergeReminders();
            $this->mergeImportantDates();
            $this->mergeTasks();
            $this->mergeNotes();
            $this->mergeCalls();
            $this->mergeGoals();
            $this->mergePets();
            $this->mergeMoodTrackingEvents();
            $this->mergeQuickFacts();
            $this->mergeFiles();
            $this->mergeFeedItems();
            $this->mergeRelationships();
            $this->mergeGroups();
            $this->mergeLifeEvents();
            $this->mergeTimelineEvents();
            $this->mergeLifeMetrics();
            $this->hideDuplicateContact();
        });
    }

    private function mergeContactInformation(): void
    {
        $existingData = $this->primaryContact->contactInformations
            ->map(fn ($info) => $this->normalizeContactInformation($info))
            ->unique()
            ->values();

        foreach ($this->duplicateContact->contactInformations as $info) {
            $normalized = $this->normalizeContactInformation($info);

            if (! $existingData->contains($normalized)) {
                $newInfo = $info->replicate();
                $newInfo->contact_id = $this->primaryContact->id;
                $newInfo->save();
                $existingData->push($normalized);
            }
        }
    }

    private function normalizeContactInformation(ContactInformation $info): string
    {
        return strtolower(trim($info->type_id . ':' . $info->data));
    }

    private function mergeAddresses(): void
    {
        $existingAddressIds = $this->primaryContact->addresses->pluck('id');

        foreach ($this->duplicateContact->addresses as $address) {
            if (! $existingAddressIds->contains($address->id)) {
                $this->primaryContact->addresses()->attach($address->id, [
                    'is_past_address' => $address->pivot->is_past_address ?? false,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }

    private function mergeLabels(): void
    {
        $existingLabelIds = $this->primaryContact->labels->pluck('id');

        foreach ($this->duplicateContact->labels as $label) {
            if (! $existingLabelIds->contains($label->id)) {
                $this->primaryContact->labels()->attach($label->id);
            }
        }
    }

    private function mergeReminders(): void
    {
        $existingReminders = $this->primaryContact->reminders
            ->map(fn ($reminder) => $this->normalizeReminder($reminder))
            ->unique()
            ->values();

        foreach ($this->duplicateContact->reminders as $reminder) {
            $normalized = $this->normalizeReminder($reminder);

            if (! $existingReminders->contains($normalized)) {
                $newReminder = $reminder->replicate();
                $newReminder->contact_id = $this->primaryContact->id;
                $newReminder->save();

                foreach ($reminder->userNotificationChannels as $channel) {
                    $newReminder->userNotificationChannels()->attach($channel->id, [
                        'scheduled_at' => $channel->pivot->scheduled_at,
                        'triggered_at' => $channel->pivot->triggered_at,
                    ]);
                }

                $existingReminders->push($normalized);
            }
        }
    }

    private function normalizeReminder(ContactReminder $reminder): string
    {
        return implode(':', [
            $reminder->label,
            $reminder->day,
            $reminder->month,
            $reminder->year,
            $reminder->type,
            $reminder->frequency_number,
        ]);
    }

    private function mergeImportantDates(): void
    {
        $existingDates = $this->primaryContact->importantDates
            ->map(fn ($date) => $this->normalizeImportantDate($date))
            ->unique()
            ->values();

        foreach ($this->duplicateContact->importantDates as $date) {
            $normalized = $this->normalizeImportantDate($date);

            if (! $existingDates->contains($normalized)) {
                $newDate = $date->replicate();
                $newDate->contact_id = $this->primaryContact->id;
                $newDate->save();
                $existingDates->push($normalized);
            }
        }
    }

    private function normalizeImportantDate(ContactImportantDate $date): string
    {
        return implode(':', [
            $date->contact_important_date_type_id,
            $date->day,
            $date->month,
            $date->year,
            $date->label,
        ]);
    }

    private function mergeTasks(): void
    {
        foreach ($this->duplicateContact->tasks as $task) {
            $newTask = $task->replicate();
            $newTask->contact_id = $this->primaryContact->id;
            $newTask->save();
        }
    }

    private function mergeNotes(): void
    {
        foreach ($this->duplicateContact->notes as $note) {
            $newNote = $note->replicate();
            $newNote->contact_id = $this->primaryContact->id;
            $newNote->save();
        }
    }

    private function mergeCalls(): void
    {
        foreach ($this->duplicateContact->calls as $call) {
            $newCall = $call->replicate();
            $newCall->contact_id = $this->primaryContact->id;
            $newCall->save();
        }
    }

    private function mergeGoals(): void
    {
        foreach ($this->duplicateContact->goals as $goal) {
            $newGoal = $goal->replicate();
            $newGoal->contact_id = $this->primaryContact->id;
            $newGoal->save();
        }
    }

    private function mergePets(): void
    {
        foreach ($this->duplicateContact->pets as $pet) {
            $newPet = $pet->replicate();
            $newPet->contact_id = $this->primaryContact->id;
            $newPet->save();
        }
    }

    private function mergeMoodTrackingEvents(): void
    {
        foreach ($this->duplicateContact->moodTrackingEvents as $event) {
            $newEvent = $event->replicate();
            $newEvent->contact_id = $this->primaryContact->id;
            $newEvent->save();
        }
    }

    private function mergeQuickFacts(): void
    {
        foreach ($this->duplicateContact->quickFacts as $quickFact) {
            $newQuickFact = $quickFact->replicate();
            $newQuickFact->contact_id = $this->primaryContact->id;
            $newQuickFact->save();
        }
    }

    private function mergeFiles(): void
    {
        foreach ($this->duplicateContact->files as $file) {
            $file->ufileable_id = $this->primaryContact->id;
            $file->ufileable_type = Contact::class;
            $file->save();
        }
    }

    private function mergeFeedItems(): void
    {
        foreach ($this->duplicateContact->feedItems as $feedItem) {
            $newFeedItem = $feedItem->replicate();
            $newFeedItem->contact_id = $this->primaryContact->id;
            $newFeedItem->save();
        }
    }

    private function mergeRelationships(): void
    {
        $existingRelationshipIds = $this->primaryContact->relationships->pluck('id');

        foreach ($this->duplicateContact->relationships as $relatedContact) {
            if (! $existingRelationshipIds->contains($relatedContact->id) && $relatedContact->id !== $this->primaryContact->id) {
                $this->primaryContact->relationships()->attach($relatedContact->id);
            }
        }

        DB::table('relationships')
            ->where('related_contact_id', $this->duplicateContact->id)
            ->update(['related_contact_id' => $this->primaryContact->id]);

        DB::table('relationships')
            ->where('contact_id', $this->duplicateContact->id)
            ->delete();
    }

    private function mergeGroups(): void
    {
        $existingGroupIds = $this->primaryContact->groups->pluck('id');

        foreach ($this->duplicateContact->groups as $group) {
            if (! $existingGroupIds->contains($group->id)) {
                $this->primaryContact->groups()->attach($group->id);
            }
        }
    }

    private function mergeLifeEvents(): void
    {
        $existingLifeEventIds = $this->primaryContact->lifeEvents->pluck('id');

        foreach ($this->duplicateContact->lifeEvents as $lifeEvent) {
            if (! $existingLifeEventIds->contains($lifeEvent->id)) {
                $this->primaryContact->lifeEvents()->attach($lifeEvent->id);
            }
        }
    }

    private function mergeTimelineEvents(): void
    {
        $existingTimelineEventIds = $this->primaryContact->timelineEvents->pluck('id');

        foreach ($this->duplicateContact->timelineEvents as $timelineEvent) {
            if (! $existingTimelineEventIds->contains($timelineEvent->id)) {
                $this->primaryContact->timelineEvents()->attach($timelineEvent->id);
            }
        }
    }

    private function mergeLifeMetrics(): void
    {
        $existingLifeMetricIds = $this->primaryContact->lifeMetrics->pluck('id');

        foreach ($this->duplicateContact->lifeMetrics as $lifeMetric) {
            if (! $existingLifeMetricIds->contains($lifeMetric->id)) {
                $this->primaryContact->lifeMetrics()->attach($lifeMetric->id);
            }
        }
    }

    private function hideDuplicateContact(): void
    {
        $this->duplicateContact->listed = false;
        $this->duplicateContact->save();

        $this->duplicateContact->unsearchable();
    }

    private function updateLastEditedDate(): void
    {
        $this->primaryContact->last_updated_at = Carbon::now();
        $this->primaryContact->save();
    }
}
