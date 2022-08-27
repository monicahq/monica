<?php

namespace App\Contact\ManageTasks\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactTask;
use App\Services\BaseService;
use Carbon\Carbon;

class UpdateContactTask extends BaseService implements ServiceInterface
{
    private ContactTask $task;

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
            'contact_task_id' => 'required|integer|exists:contact_tasks,id',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string|max:65535',
            'due_at' => 'nullable|date',
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
            'contact_must_belong_to_vault',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Update a task.
     *
     * @param  array  $data
     * @return ContactTask
     */
    public function execute(array $data): ContactTask
    {
        $this->validateRules($data);

        $this->task = ContactTask::where('contact_id', $data['contact_id'])
            ->findOrFail($data['contact_task_id']);

        $this->task->label = $data['label'];
        $this->task->description = $this->valueOrNull($data, 'description');
        $this->task->due_at = $this->valueOrNull($data, 'due_at');
        $this->task->save();

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

        return $this->task;
    }
}
