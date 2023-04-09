<?php

namespace App\Domains\Contact\ManageLifeEvents\Services;

use App\Interfaces\ServiceInterface;
use App\Models\TimelineEvent;
use App\Services\BaseService;

class UpdateTimelineEvent extends BaseService implements ServiceInterface
{
    private TimelineEvent $timelineEvent;

    private array $data;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'timeline_event_id' => 'required|integer|exists:timeline_events,id',
            'label' => 'nullable|string|max:255',
            'started_at' => 'required|date|date_format:Y-m-d',
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
     * Update a timeline event.
     */
    public function execute(array $data): TimelineEvent
    {
        $this->data = $data;
        $this->validate();
        $this->update();

        return $this->timelineEvent;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->timelineEvent = $this->vault->timelineEvents()
            ->findOrFail($this->data['timeline_event_id']);
    }

    private function update(): void
    {
        $this->timelineEvent->label = $this->valueOrNull($this->data, 'label');
        $this->timelineEvent->started_at = $this->data['started_at'];
        $this->timelineEvent->save();
    }
}
