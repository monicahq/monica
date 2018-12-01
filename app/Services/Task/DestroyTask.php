<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



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
