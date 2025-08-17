<?php

namespace App\Domains\Contact\ManageTasks\Services;

use App\Interfaces\ServiceInterface;
use App\Services\QueuableService;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Traits\Localizable;

class DestroyContactTask extends QueuableService implements ServiceInterface
{
    use Batchable, Localizable;

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
            'contact_task_id' => 'required|integer|exists:contact_tasks,id',
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
     * Destroy a task.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $task = $this->contact->tasks()
            ->findOrFail($data['contact_task_id']);

        $task->delete();

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }
}
