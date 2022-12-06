<?php

namespace App\Domains\Contact\ManageLifeContactEvents\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactLifeEvent;
use App\Models\LifeEventType;
use App\Services\BaseService;
use Carbon\Carbon;

class CreateContactLifeEvent extends BaseService implements ServiceInterface
{
    private ContactLifeEvent $contactLifeEvent;

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
            'contact_id' => 'required|integer|exists:contacts,id',
            'life_event_type_id' => 'required|integer|exists:life_event_types,id',
            'summary' => 'required|string|max:255',
            'started_at' => 'date|date_format:Y-m-d',
            'ended_at' => 'date|date_format:Y-m-d',
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
            'contact_must_belong_to_vault',
        ];
    }

    /**
     * Create a contact event.
     *
     * @param  array  $data
     * @return ContactLifeEvent
     */
    public function execute(array $data): ContactLifeEvent
    {
        $this->data = $data;
        $this->validate();
        $this->updateLastEditedDate();
        $this->store();

        return $this->contactLifeEvent;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $lifeEventType = LifeEventType::findOrFail($this->data['life_event_type_id']);

        $this->account()->lifeEventCategories()
            ->findOrFail($lifeEventType->lifeEventCategory->id);
    }

    private function updateLastEditedDate(): void
    {
        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }

    private function store(): void
    {
        $this->contactLifeEvent = ContactLifeEvent::create([
            'contact_id' => $this->data['contact_id'],
            'life_event_type_id' => $this->data['life_event_type_id'],
            'summary' => $this->data['summary'],
            'started_at' => $this->data['started_at'],
            'ended_at' => $this->data['ended_at'],
        ]);

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }
}
