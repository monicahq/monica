<?php

namespace App\Domains\Contact\ManageMoodTrackingEvents\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactFeedItem;
use App\Models\MoodTrackingEvent;
use App\Services\BaseService;
use Carbon\Carbon;

class UpdateMoodTrackingEvent extends BaseService implements ServiceInterface
{
    private MoodTrackingEvent $moodTrackingEvent;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'contact_id' => 'required|uuid|exists:contacts,id',
            'mood_tracking_parameter_id' => 'required|integer|exists:mood_tracking_parameters,id',
            'mood_tracking_event_id' => 'required|integer|exists:mood_tracking_events,id',
            'rated_at' => 'required|date_format:Y-m-d',
            'note' => 'nullable|string|max:65535',
            'number_of_hours_slept' => 'nullable|integer',
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
            'contact_must_belong_to_vault',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Update a mood tracking event.
     */
    public function execute(array $data): MoodTrackingEvent
    {
        $this->validateRules($data);

        $parameter = $this->vault->moodTrackingParameters()
            ->findOrFail($data['mood_tracking_parameter_id']);

        $this->moodTrackingEvent = $this->contact->moodTrackingEvents()
            ->where('mood_tracking_parameter_id', $parameter->id)
            ->findOrFail($data['mood_tracking_event_id']);

        $this->moodTrackingEvent->mood_tracking_parameter_id = $data['mood_tracking_parameter_id'];
        $this->moodTrackingEvent->rated_at = $data['rated_at'];
        $this->moodTrackingEvent->note = $this->valueOrNull($data, 'note');
        $this->moodTrackingEvent->number_of_hours_slept = $this->valueOrNull($data, 'number_of_hours_slept');
        $this->moodTrackingEvent->save();

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

        $this->createFeedItem();

        return $this->moodTrackingEvent;
    }

    private function createFeedItem(): void
    {
        $feedItem = ContactFeedItem::create([
            'author_id' => $this->author->id,
            'contact_id' => $this->contact->id,
            'action' => ContactFeedItem::ACTION_MOOD_TRACKING_EVENT_UPDATED,
            'description' => $this->moodTrackingEvent->moodTrackingParameter->label,
        ]);

        $this->moodTrackingEvent->feedItem()->save($feedItem);
    }
}
