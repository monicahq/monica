<?php

namespace App\Services\Task;

use App\Models\Contact\Task;
use App\Services\BaseService;

class UpdateTask extends BaseService
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
            'task_id' => 'required|integer|exists:tasks,id',
            'title' => 'required|string:255',
            'description' => 'nullable|string:400000000',
            'completed' => 'required|boolean',
        ];
    }

    /**
     * Update a task.
     *
     * @param  array  $data
     * @return Task
     */
    public function execute(array $data): Task
    {
        $this->validate($data);

        if (! empty($data['contact_id'])) {
            /** @var Task */
            $task = Task::where('account_id', $data['account_id'])
                ->where('contact_id', $data['contact_id'])
                ->findOrFail($data['task_id']);
        } else {
            /** @var Task */
            $task = Task::where('account_id', $data['account_id'])
                ->findOrFail($data['task_id']);
        }

        $task->update([
            'title' => $data['title'],
            'description' => (! empty($data['description']) ? $data['description'] : null),
            'completed' => $data['completed'],
            'completed_at' => ($data['completed'] == true ? now() : null),
        ]);

        return $task;
    }
}
