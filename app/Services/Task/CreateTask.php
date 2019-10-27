<?php

namespace App\Services\Task;

use App\Models\Contact\Task;
use App\Services\BaseService;
use App\Models\Contact\Contact;

class CreateTask extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'contact_id' => 'nullable|integer',
            'title' => 'required|string:255',
            'description' => 'nullable|string:400000000',
        ];
    }

    /**
     * Create a task.
     *
     * @param array $data
     * @return Task
     */
    public function execute(array $data) : Task
    {
        $this->validate($data);

        if (! empty($data['contact_id'])) {
            Contact::where('account_id', $data['account_id'])
                ->findOrFail($data['contact_id']);
        }

        $task = Task::create($data);

        return Task::find($task->id);
    }
}
