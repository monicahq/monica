<?php

namespace App\Services\Task;

use App\Models\Contact\Task;
use App\Services\BaseService;

class DestroyTask extends BaseService
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
            'task_id' => 'required|integer',
        ];
    }

    /**
     * Destroy a task.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data) : bool
    {
        $this->validate($data);

        $task = Task::where('account_id', $data['account_id'])
            ->findOrFail($data['task_id']);

        // Delete the object in the DB
        $task->delete();

        return true;
    }
}
