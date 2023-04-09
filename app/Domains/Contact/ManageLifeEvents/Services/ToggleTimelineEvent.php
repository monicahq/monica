<?php

namespace App\Domains\Contact\ManageLifeEvents\Services;

use App\Interfaces\ServiceInterface;
use App\Models\TimelineEvent;
use App\Services\BaseService;

class ToggleTimelineEvent extends BaseService implements ServiceInterface
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
     * Toggle a timeline event.
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
        $this->timelineEvent->collapsed = ! $this->timelineEvent->collapsed;
        $this->timelineEvent->save();
    }
}
