<?php

namespace Tests\Unit\Services\Task;

use Tests\TestCase;
use App\Models\Contact\Task;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Services\Task\UpdateTask;
use Illuminate\Validation\ValidationException;
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

        $task = app(UpdateTask::class)->execute($request);

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

        $task = app(UpdateTask::class)->execute($request);

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

        $this->expectException(ValidationException::class);

        app(UpdateTask::class)->execute($request);
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

        app(UpdateTask::class)->execute($request);
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

        app(UpdateTask::class)->execute($request);
    }
}
