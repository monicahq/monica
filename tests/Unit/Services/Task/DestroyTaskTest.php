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

namespace Tests\Unit\Services\Contact\Document;

use Tests\TestCase;
use App\Models\Contact\Task;
use App\Models\Account\Account;
use App\Services\Task\DestroyTask;
use App\Exceptions\MissingParameterException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DestroyTaskTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_destroys_a_task()
    {
        $task = factory(Task::class)->create([]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
        ]);

        $request = [
            'account_id' => $task->account->id,
            'task_id' => $task->id,
        ];

        $destroyTaskService = new DestroyTask;
        $bool = $destroyTaskService->execute($request);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'task_id' => 2,
        ];

        $this->expectException(MissingParameterException::class);

        $destroyTaskService = new DestroyTask;
        $result = $destroyTaskService->execute($request);
    }

    public function test_it_throws_a_task_doesnt_exist()
    {
        $task = factory(Task::class)->create([]);
        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'task_id' => $task->id,
        ];

        $this->expectException(ModelNotFoundException::class);

        $destroyTaskService = new DestroyTask;
        $task = $destroyTaskService->execute($request);
    }
}
