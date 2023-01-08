<?php

namespace App\Domains\Contact\ManageLifeEvents\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventType;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class UpdateLifeEvent extends BaseService implements ServiceInterface
{
    private LifeEvent $lifeEvent;

    private Collection $partipantsCollection;

    private array $data;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'vault_id' => 'required|integer|exists:vaults,id',
            'author_id' => 'required|integer|exists:users,id',
            'life_event_type_id' => 'required|integer|exists:life_event_types,id',
            'life_event_id' => 'required|integer|exists:life_events,id',
            'summary' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:65535',
            'happened_at' => 'required|date|date_format:Y-m-d',
            'costs' => 'nullable|integer',
            'currency_id' => 'nullable|integer|exists:currencies,id',
            'paid_by_contact_id' => 'nullable|integer|exists:contacts,id',
            'duration_in_minutes' => 'nullable|integer',
            'distance_in_km' => 'nullable|integer',
            'from_place' => 'nullable|string|max:255',
            'to_place' => 'nullable|string|max:255',
            'place' => 'nullable|string|max:255',
            'participant_ids' => 'required|array',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     *
     * @return array
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
     * Update a life event.
     *
     * @param  array  $data
     * @return LifeEvent
     */
    public function execute(array $data): LifeEvent
    {
        $this->data = $data;
        $this->validate();
        $this->update();
        $this->associateParticipants();
        $this->updateLastEditedDate();

        return $this->lifeEvent;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->lifeEvent = $this->vault->lifeEvents()
            ->findOrFail($this->data['life_event_id']);

        $lifeEventType = LifeEventType::findOrFail($this->data['life_event_type_id']);

        $this->vault->lifeEventCategories()
            ->findOrFail($lifeEventType->lifeEventCategory->id);

        if (! is_null($this->data['paid_by_contact_id'])) {
            $this->vault->contacts()
                ->findOrFail($this->data['paid_by_contact_id']);
        }

        if (! is_null($this->data['currency_id'])) {
            $this->account()->currencies()
                ->findOrFail($this->data['currency_id']);
        }

        $this->partipantsCollection = collect($this->data['participant_ids'])
            ->map(fn (int $participantId): Contact => $this->vault->contacts()->findOrFail($participantId));
    }

    private function update(): void
    {
        $this->lifeEvent->life_event_type_id = $this->data['life_event_type_id'];
        $this->lifeEvent->summary = $this->valueOrNull($this->data, 'summary');
        $this->lifeEvent->description = $this->valueOrNull($this->data, 'description');
        $this->lifeEvent->happened_at = $this->data['happened_at'];
        $this->lifeEvent->costs = $this->valueOrNull($this->data, 'costs');
        $this->lifeEvent->currency_id = $this->valueOrNull($this->data, 'currency_id');
        $this->lifeEvent->paid_by_contact_id = $this->valueOrNull($this->data, 'paid_by_contact_id');
        $this->lifeEvent->duration_in_minutes = $this->valueOrNull($this->data, 'duration_in_minutes');
        $this->lifeEvent->distance_in_km = $this->valueOrNull($this->data, 'distance_in_km');
        $this->lifeEvent->from_place = $this->valueOrNull($this->data, 'from_place');
        $this->lifeEvent->to_place = $this->valueOrNull($this->data, 'to_place');
        $this->lifeEvent->place = $this->valueOrNull($this->data, 'place');
        $this->lifeEvent->save();
    }

    private function updateLastEditedDate(): void
    {
        foreach ($this->partipantsCollection as $participant) {
            $participant->last_updated_at = Carbon::now();
            $participant->save();
        }
    }

    private function associateParticipants(): void
    {
        foreach ($this->partipantsCollection as $participant) {
            $participant->lifeEvents()->attach($this->lifeEvent->id);
        }
    }
}
