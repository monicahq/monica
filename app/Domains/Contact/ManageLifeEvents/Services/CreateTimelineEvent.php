<?php

namespace App\Domains\Contact\ManageLifeEvents\Services;

use App\Interfaces\ServiceInterface;
use App\Models\TimelineEvent;
use App\Services\BaseService;

class CreateTimelineEvent extends BaseService implements ServiceInterface
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
     * Create a timeline event.
     * A timeline event is a part of one or more contacts lives, and is itself
     * composed of one or more life events.
     */
    public function execute(array $data): TimelineEvent
    {
        $this->data = $data;
        $this->validate();
        $this->store();

        return $this->timelineEvent;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
    }

    private function store(): void
    {
        $this->timelineEvent = TimelineEvent::create([
            'vault_id' => $this->data['vault_id'],
            'label' => $this->valueOrNull($this->data, 'summary'),
            'started_at' => $this->data['started_at'],
        ]);
    }
}
