<?php

namespace App\Domains\Contact\ManageTasks\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactTask;
use App\Services\BaseService;
use Carbon\Carbon;

class CreateContactTask extends BaseService implements ServiceInterface
{
    private ContactTask $task;

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
            'contact_id' => 'required|uuid|exists:contacts,id',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string|max:65535',
            'due_at' => 'nullable|date',
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
            'contact_must_belong_to_vault',
        ];
    }

    /**
     * Create a contact task.
     */
    public function execute(array $data): ContactTask
    {
        $this->validateRules($data);
        $this->data = $data;

        $this->createContactTask();
        $this->updateLastEditedDate();

        return $this->task;
    }

    private function createContactTask(): void
    {
        $this->task = ContactTask::create([
            'contact_id' => $this->data['contact_id'],
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'label' => $this->data['label'],
            'description' => $this->valueOrNull($this->data, 'description'),
            'due_at' => $this->valueOrNull($this->data, 'due_at'),
        ]);
    }

    private function updateLastEditedDate(): void
    {
        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }
}
