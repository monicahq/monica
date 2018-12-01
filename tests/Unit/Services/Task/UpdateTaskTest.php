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



namespace Tests\Unit\Services\Task;

use Tests\TestCase;
use App\Models\Contact\Task;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Services\Task\UpdateTask;
use App\Exceptions\MissingParameterException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateTaskTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_updates_a_task_associated_with_a_contact()
    {
        $task = factory(Task::class)->create([]);

        $request = [
            'account_id' => $task->account->id,
            'contact_id' => $task->contact->id,
            'task_id' => $task->id,
            'title' => 'title',
            'description' => 'description',
            'completed' => true,
        ];

        $taskService = new UpdateTask;
        $task = $taskService->execute($request);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'contact_id' => $task->contact->id,
            'title' => 'title',
            'description' => 'description',
            'completed' => 1,
        ]);

        $this->assertInstanceOf(
            Task::class,
            $task
        );
    }

    public function test_it_updates_a_task_associated_without_a_contact()
    {
        $task = factory(Task::class)->create([
            'contact_id' => null,
        ]);

        $request = [
            'account_id' => $task->account->id,
            'task_id' => $task->id,
            'title' => 'title',
            'description' => 'description',
            'completed' => true,
        ];

        $taskService = new UpdateTask;
        $task = $taskService->execute($request);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'contact_id' => null,
            'title' => 'title',
            'description' => 'description',
            'completed' => 1,
        ]);

        $this->assertInstanceOf(
            Task::class,
            $task
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $task = factory(Task::class)->create([]);

        $request = [
            'account_id' => $task->account->id,
            'task_id' => $task->id,
            'title' => 'title',
            'description' => 'description',
        ];

        $this->expectException(MissingParameterException::class);

        $updateTask = new UpdateTask;
        $task = $updateTask->execute($request);
    }

    public function test_it_throws_an_exception_if_contact_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create();
        $task = factory(Task::class)->create([]);

        $request = [
            'account_id' => $task->account->id,
            'contact_id' => $contact->id,
            'task_id' => $task->id,
            'title' => 'title',
            'description' => 'description',
            'completed' => false,
        ];

        $this->expectException(ModelNotFoundException::class);

        $updateTask = (new UpdateTask)->execute($request);
    }

    public function test_it_throws_an_exception_if_task_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create();
        $task = factory(Task::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'task_id' => $task->id,
            'title' => 'title',
            'description' => 'description',
            'completed' => false,
        ];

        $this->expectException(ModelNotFoundException::class);

        $updateTask = (new UpdateTask)->execute($request);
    }
}
