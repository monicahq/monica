<?php

namespace App\Domains\Contact\ManageLifeEvents\Services;

use App\Interfaces\ServiceInterface;
use App\Models\LifeEvent;
use App\Models\TimelineEvent;
use App\Services\BaseService;

class DestroyLifeEvent extends BaseService implements ServiceInterface
{
    private LifeEvent $lifeEvent;

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
            'life_event_id' => 'required|integer|exists:life_events,id',
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
     * Destroy a life event.
     */
    public function execute(array $data): void
    {
        $this->data = $data;
        $this->validate();

        $this->lifeEvent->delete();

        $this->deleteTimelineEvent();
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->timelineEvent = $this->vault->timelineEvents()
            ->findOrFail($this->data['timeline_event_id']);

        $this->lifeEvent = $this->timelineEvent->lifeEvents()
            ->findOrFail($this->data['life_event_id']);
    }

    private function deleteTimelineEvent(): void
    {
        // a LifeEvent is always associated with a timeline event
        // if we delete the last life event of the timeline event, we need to
        // delete the timeline event as well
        if ($this->timelineEvent->lifeEvents()->count() === 0) {
            $this->timelineEvent->delete();
        }
    }
}
